<?php

namespace library\service\arbitrage;

use library\dao\arbitrage\DayPlanDao;
use library\model\arbitrage\DayPlanModel;
use library\model\arbitrage\ProjectModel;
use library\model\arbitrage\ProjectOrderModel;
use support\extend\Service;

/**
 * 套利日计划：由业务侧每日 0 点创建，引擎只读取并执行。
 *
 * 状态约定：
 * - 1 PENDING / 2 RUNNING：可执行
 * - 3 DONE：已达目标收益率，不再执行
 * - 4 CLOSED：窗口耗尽未达标（可补救则短时捞回）
 *
 * @method DayPlanModel create($data)
 * @method DayPlanModel updateOrCreate(array $params, array $data)
 * @method DayPlanModel update($id, array $data)
 * @method DayPlanModel get($id, string $field = null)
 * @method DayPlanModel find($id)
 * @method DayPlanModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 */
class DayPlanService extends Service
{
    public function __construct()
    {
        $this->dao = DayPlanDao::class;
        parent::__construct();
    }

    /**
     * 计划是否具备执行条件（字段应由 0 点建计划任务写入，引擎不补齐）。
     */
    public function isExecutable(DayPlanModel $plan): bool
    {
        return (int) $plan->project_id > 0
            && (float) $plan->target_amount > 0
            && (float) $plan->target_profit > 0
            && (int) $plan->target_trades > 0
            && $plan->getSchedule() !== [];
    }

    /**
     * 根据矿机项目创建日计划
     * @param ProjectModel $projectObj
     * @return DayPlanModel
     */
    public function createPlanByProject(ProjectModel $projectObj){
        $projectOrderSvc = new ProjectOrderService();
        $projectOrderSvc->updateAll([
            'project_id'=>$projectObj->id,
            'status'      => ProjectOrderModel::STATUS_PENDING,
            'expires_at'=> ['gt',date('Y-m-d H:i:s')],
        ],['status'=>ProjectOrderModel::STATUS_RUNNING]);
        $amount = $projectOrderSvc->sumActiveOrderAmount($projectObj->id);
        if ($amount <= 0) {
            return null;
        }
        $targetRate = rand((float)$projectObj->min_day_rate*1000,(float)$projectObj->max_day_rate*1000)/100000;
        $targetProfit = round($amount * $targetRate, 2);
        // 与引擎 getPendingPlans 一致：按业务时区（默认美东）切日
        $plan_day = (new \DateTimeImmutable(
            'now',
            new \DateTimeZone((string) (config('arbitrage.business_timezone') ?: 'America/New_York'))
        ))->format('Y-m-d');
        $exists = $this->fetch([
            'project_id' => $projectObj->id,
            'day'        => $plan_day,
        ]);
        if(!empty($exists)){
            return null;
        }
        $targetTrades = max(3, (int) $projectObj->position_cnt);
        $tz = (string) (config('arbitrage.business_timezone') ?: 'America/New_York');
        // 引擎 isExecutable 要求 schedule 非空；0 点建计划时必须写入窗口
        $schedule = \support\arbitrage\math\Stake::generateSchedule($targetTrades, $tz);
        $data = [
            'project_id'      => $projectObj->id,
            'day'             => $plan_day,
            'target_amount'   => round($amount, 2),
            'target_rate'     => $targetRate,
            'target_profit'   => $targetProfit,
            'realized_profit' => 0,
            'target_trades'   => $targetTrades,
            'done_trades'     => 0,
            'schedule'        => json_encode($schedule, JSON_UNESCAPED_UNICODE),
            'next_idx'        => 0,
            'last_attempt_at' => 0,
            'bailout_count'   => 0,
            'created_time'    => time(),
            'updated_time'    => time(),
            'status'          => DayPlanModel::STATUS_PENDING,
        ];
        return $this->create($data);
    }

    /**
     * 给缺失 schedule 的计划补窗口（修复历史脏数据）。
     */
    public function ensureSchedule(DayPlanModel $plan): DayPlanModel
    {
        if ($plan->getSchedule() !== []) {
            return $plan;
        }
        $count = max(1, (int) $plan->target_trades);
        $tz = (string) (config('arbitrage.business_timezone') ?: 'America/New_York');
        $schedule = \support\arbitrage\math\Stake::generateSchedule($count, $tz);
        $this->update((int) $plan->id, [
            'schedule'     => json_encode($schedule, JSON_UNESCAPED_UNICODE),
            'next_idx'     => 0,
            'updated_time' => time(),
            'status'       => DayPlanModel::STATUS_PENDING,
        ]);
        return $this->get((int) $plan->id) ?: $plan;
    }

    /**
     * 待执行计划：不含 status=3(已完成)。
     * 若 RUNNING/PENDING 已达标则立刻标 DONE 并跳过。
     *
     * @return list<DayPlanModel>
     */
    public function getPendingPlans(?string $day = null): array
    {
        $day ??= $this->businessDay((string) (config('arbitrage.business_timezone') ?: 'America/New_York'));
        $maxBailout = (int) (config('arbitrage.engine.bailout_max_rounds') ?? 2);
        $maxRedeploy = (int) (config('arbitrage.engine.settle_redeploy_max_rounds') ?? 2);
        $maxExtend = $maxBailout + max(0, $maxRedeploy);

        $rows = $this->fetchAll([
            'day'    => $day,
            'status' => ['in', [
                DayPlanModel::STATUS_PENDING,
                DayPlanModel::STATUS_RUNNING,
                DayPlanModel::STATUS_CLOSED,
            ]],
            'size'   => 2000,
        ]);

        $out = [];
        foreach ($rows as $r) {
            // 已达收益率 → 标完成，后续不再执行
            if ($r->goalMet()) {
                $this->markDone($r);
                continue;
            }

            $status = (int) $r->status;
            if ($status === DayPlanModel::STATUS_PENDING || $status === DayPlanModel::STATUS_RUNNING) {
                $out[] = $r;
                continue;
            }
            // CLOSED 但仍可能补救 / 结算复用
            if ($status === DayPlanModel::STATUS_CLOSED
                && (int) $r->bailout_count < $maxExtend) {
                $out[] = $r;
            }
        }
        return $out;
    }

    /**
     * 调度用可执行计划列表。
     * - 默认：当日 PENDING/RUNNING/可补救 CLOSED
     * - 补偿开启：再并入历史未达标计划；同一 project_id 只保留最老的一条，串行补达标
     *
     * @return list<DayPlanModel>
     */
    public function getExecutablePlans(?string $day = null): array
    {
        $day ??= $this->businessDay((string) (config('arbitrage.business_timezone') ?: 'America/New_York'));
        $comp = (array) (config('arbitrage.engine.compensation') ?: []);
        $enabled = !empty($comp['enabled']);

        $byId = [];
        foreach ($this->getPendingPlans($day) as $p) {
            $byId[(int) $p->id] = $p;
        }

        if ($enabled && !empty($comp['include_past_days'])) {
            foreach ($this->listIncompletePlans() as $p) {
                $byId[(int) $p->id] = $p;
            }
        }

        $plans = array_values($byId);
        // 最老 day 优先、同日 id 小优先
        usort($plans, static function ($a, $b) {
            $da = (string) $a->day;
            $db = (string) $b->day;
            if ($da !== $db) {
                return $da <=> $db;
            }
            return ((int) $a->id) <=> ((int) $b->id);
        });

        // 同矿机串行：每个 project 只跑一条未达标计划
        $seenProject = [];
        $out = [];
        foreach ($plans as $p) {
            if ($p->goalMet()) {
                $this->markDone($p);
                continue;
            }
            $pid = (int) $p->project_id;
            if (isset($seenProject[$pid])) {
                continue;
            }
            $seenProject[$pid] = true;
            $out[] = $p;
        }

        $max = max(1, (int) ($comp['max_plans_per_tick'] ?? 30));
        return array_slice($out, 0, $max);
    }

    /**
     * 全部未达标计划（PENDING/RUNNING/CLOSED 且利润未达目标）。
     *
     * @return list<DayPlanModel>
     */
    public function listIncompletePlans(): array
    {
        $rows = $this->fetchAll([
            'status' => ['in', [
                DayPlanModel::STATUS_PENDING,
                DayPlanModel::STATUS_RUNNING,
                DayPlanModel::STATUS_CLOSED,
            ]],
            'size'   => 5000,
        ], ['day' => 'asc', 'id' => 'asc']);

        $out = [];
        foreach ($rows as $r) {
            if ((float) $r->target_amount <= 0 || (float) $r->target_profit <= 0) {
                continue;
            }
            if ($r->goalMet()) {
                $this->markDone($r);
                continue;
            }
            $out[] = $r;
        }
        return $out;
    }

    public function compensationEnabled(): bool
    {
        return !empty(config('arbitrage.engine.compensation.enabled'));
    }

    public function compensationNeverClose(): bool
    {
        return $this->compensationEnabled()
            && !empty(config('arbitrage.engine.compensation.never_close'));
    }

    /**
     * 补偿模式：允许无限追加补救/复用窗（不受 bailout 上限约束）。
     */
    public function canAlwaysExtend(DayPlanModel $plan): bool
    {
        return $this->compensationEnabled()
            && !$plan->goalMet()
            && (int) $plan->status !== DayPlanModel::STATUS_DONE;
    }

    /**
     * 重新激活未达标计划：补 schedule、置 RUNNING、立刻挂上新窗口。
     */
    public function reactivateForCompensation(DayPlanModel $plan): DayPlanModel
    {
        if ($plan->goalMet()) {
            return $this->markDone($plan);
        }

        if ($plan->getSchedule() === []
            || (int) $plan->target_trades <= 0) {
            if ((int) $plan->target_trades <= 0) {
                $this->update((int) $plan->id, [
                    'target_trades' => max(3, (int) $plan->done_trades + 2),
                    'updated_time'  => time(),
                ]);
                $plan = $this->get((int) $plan->id) ?: $plan;
            }
            $plan = $this->ensureSchedule($plan);
        }

        $schedule = $plan->getSchedule();
        $nextIdx = (int) $plan->next_idx;
        $windows = max(1, (int) (config('arbitrage.engine.compensation.windows_per_reactivate') ?? 3));
        $now = time();

        // 窗已耗尽、或当前窗已过期超过 1 小时：直接挂「立刻可执行」的新窗
        $needWindows = $nextIdx >= count($schedule);
        if (!$needWindows && isset($schedule[$nextIdx])) {
            $windowSec = (int) (config('arbitrage.engine.trade_window_seconds') ?? 3600);
            if ($now > (int) $schedule[$nextIdx] + $windowSec) {
                $needWindows = true;
            }
        }

        if ($needWindows) {
            for ($i = 0; $i < $windows; $i++) {
                $schedule[] = $now + 15 + $i * 90;
            }
            $this->update((int) $plan->id, [
                'schedule'        => json_encode($schedule, JSON_UNESCAPED_UNICODE),
                'status'          => DayPlanModel::STATUS_RUNNING,
                'last_attempt_at' => 0,
                // 补偿不抬高关闭门槛：清零补救计数，允许继续
                'bailout_count'   => 0,
                'updated_time'    => $now,
            ]);
            return $this->get((int) $plan->id) ?: $plan;
        }

        if ((int) $plan->status === DayPlanModel::STATUS_CLOSED
            || (int) $plan->status === DayPlanModel::STATUS_PENDING) {
            $this->update((int) $plan->id, [
                'status'          => DayPlanModel::STATUS_RUNNING,
                'last_attempt_at' => 0,
                'bailout_count'   => 0,
                'updated_time'    => $now,
            ]);
            return $this->get((int) $plan->id) ?: $plan;
        }

        return $plan;
    }

    /**
     * 运维：一次性唤起所有未达标计划（写库），返回处理条数。
     */
    public function activateAllCompensation(): int
    {
        if (!$this->compensationEnabled()) {
            return 0;
        }
        $n = 0;
        foreach ($this->listIncompletePlans() as $plan) {
            // 唤起全部；执行时仍按 project 串行
            $this->reactivateForCompensation($plan);
            $n++;
        }
        return $n;
    }

        /** 标记计划已完成（status=3），之后调度不会再捞到 */
    public function markDone(DayPlanModel $plan): DayPlanModel
    {
        if ((int) $plan->status === DayPlanModel::STATUS_DONE) {
            return $plan;
        }
        $this->update((int) $plan->id, [
            'status'       => DayPlanModel::STATUS_DONE,
            'updated_time' => time(),
        ]);
        return $this->get((int) $plan->id) ?: $plan;
    }

    public function canBailout(DayPlanModel $plan): bool
    {
        if ($this->canAlwaysExtend($plan)) {
            return true;
        }
        $max = (int) (config('arbitrage.engine.bailout_max_rounds') ?? 2);
        return !$plan->goalMet()
            && (int) $plan->status !== DayPlanModel::STATUS_DONE
            && (int) $plan->bailout_count < $max;
    }

    /**
     * 常规 bailout 用尽后，若本金已释放且未达标，允许再追加 settle_redeploy_max_rounds 轮。
     * 补偿模式下等同 canAlwaysExtend。
     */
    public function canSettleRedeploy(DayPlanModel $plan): bool
    {
        if ($this->canAlwaysExtend($plan)) {
            return true;
        }
        $bailoutMax = (int) (config('arbitrage.engine.bailout_max_rounds') ?? 2);
        $redeployMax = (int) (config('arbitrage.engine.settle_redeploy_max_rounds') ?? 2);
        $ceil = $bailoutMax + max(0, $redeployMax);
        return !$plan->goalMet()
            && (int) $plan->status !== DayPlanModel::STATUS_DONE
            && (int) $plan->bailout_count < $ceil;
    }

    public function addBailoutWindows(DayPlanModel $plan, ?int $windowCount = null): DayPlanModel
    {
        if ($plan->goalMet()) {
            return $this->markDone($plan);
        }

        $windowCount ??= (int) (config('arbitrage.engine.bailout_windows') ?? 2);
        return $this->appendWindows($plan, max(1, $windowCount));
    }

    /**
     * 结算后本金可复用、常规补救已尽：再追加窗口。
     */
    public function addSettleRedeployWindows(DayPlanModel $plan, ?int $windowCount = null): DayPlanModel
    {
        if ($plan->goalMet()) {
            return $this->markDone($plan);
        }
        if (!$this->canSettleRedeploy($plan)) {
            return $plan;
        }
        $windowCount ??= (int) (config('arbitrage.engine.settle_redeploy_windows')
            ?? config('arbitrage.engine.bailout_windows')
            ?? 2);
        return $this->appendWindows($plan, max(1, $windowCount));
    }

    private function appendWindows(DayPlanModel $plan, int $windowCount): DayPlanModel
    {
        $now = time();
        $schedule = $plan->getSchedule();
        for ($i = 0; $i < $windowCount; $i++) {
            $schedule[] = $now + 30 + $i * 120;
        }

        $this->update((int) $plan->id, [
            'schedule'        => json_encode($schedule, JSON_UNESCAPED_UNICODE),
            'bailout_count'   => (int) $plan->bailout_count + 1,
            'last_attempt_at' => 0,
            'status'          => DayPlanModel::STATUS_RUNNING,
            'updated_time'    => $now,
        ]);

        return $this->get((int) $plan->id) ?: $plan;
    }

    /**
     * 加载项目日收益上下限（百分比），用于校验计划/仓位是否有效。
     *
     * @return array{min:float,max:float}|null
     */
    public function getProjectDayRateRange(int $projectId): ?array
    {
        $project = (new ProjectService())->getActiveProject($projectId);
        if (!$project) {
            // 下架项目仍可读区间做校验
            $project = (new ProjectService())->get($projectId);
        }
        if (!$project instanceof ProjectModel) {
            return null;
        }
        $min = (float) $project->min_day_rate;
        $max = (float) $project->max_day_rate;
        return [
            'min' => min($min, $max),
            'max' => max($min, $max),
        ];
    }

    /**
     * 计划在项目收益区间内是否有效（target_rate 落在 min~max）。
     */
    public function isPlanRateValid(DayPlanModel $plan): bool
    {
        $range = $this->getProjectDayRateRange((int) $plan->project_id);
        if ($range === null) {
            return false;
        }
        return $plan->isTargetRateInRange($range['min'], $range['max']);
    }

    /**
     * 按项目最高日收益率，计算当日允许的最大利润（不可突破）。
     */
    public function maxAllowedProfit(DayPlanModel $plan): float
    {
        $range = $this->getProjectDayRateRange((int) $plan->project_id);
        $amount = (float) $plan->target_amount;
        if ($range === null || $amount <= 0) {
            return (float) $plan->target_profit;
        }
        return round($amount * ($range['max'] / 100.0), 2);
    }

    public function businessDay(string $timezone): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone($timezone)))->format('Y-m-d');
    }
}
