<?php
declare(strict_types=1);

namespace app\command\arbitrage;

use library\model\arbitrage\DayPlanModel;
use library\service\arbitrage\DayPlanService;
use library\service\arbitrage\PositionService;
use library\service\arbitrage\ProjectOrderService;
use library\service\arbitrage\ProjectService;
use library\service\arbitrage\SignalService;
use support\arbitrage\ArbitrageEngine;
use support\Db;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 矿机套利链路联调：模拟 0 点日计划 → 按窗口生成仓位。
 *
 * 注意：仅用于开发/测试。正式日计划应由业务侧每日 0 点任务创建；
 * 本命令在缺少当日计划时会按项目规则临时写入一条完整 day_plan。
 *
 * 用法：
 *   php webman arbitrage:flow-test --project=1
 *   php webman arbitrage:flow-test --project=1 --amount=10000 --force-window --rounds=5
 *   php webman arbitrage:flow-test --project=1 --sync --force-window
 */
class FlowTest extends Command
{
    protected static $defaultName = 'arbitrage:flow-test';
    protected static $defaultDescription = '矿机套利链路测试（日计划 → 窗口下单 → 仓位）';

    protected function configure(): void
    {
        $this
            ->addOption('project', null, InputOption::VALUE_REQUIRED, '矿机项目 ID（默认取第一个上架项目）')
            ->addOption('amount', null, InputOption::VALUE_REQUIRED, '当日投入金额（默认取运营中订单合计或项目单价）')
            ->addOption('rounds', null, InputOption::VALUE_REQUIRED, '连续跑下单窗口次数', '3')
            ->addOption('force-window', null, InputOption::VALUE_NONE, '把计划窗口改成立即到期，便于马上出单')
            ->addOption('sync', null, InputOption::VALUE_NONE, '先同步比赛并采集信号');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $projectId = (int) ($input->getOption('project') ?: 0);
        $amount = (float) ($input->getOption('amount') ?: 0);
        $rounds = max(1, (int) ($input->getOption('rounds') ?: 3));
        $forceWindow = (bool) $input->getOption('force-window');
        $doSync = (bool) $input->getOption('sync');

        $engine = new ArbitrageEngine();
        $planSvc = new DayPlanService();
        $posSvc = new PositionService();
        $sigSvc = new SignalService();
        $projectSvc = new ProjectService();

        $output->writeln('<info>=== arbitrage project flow test ===</info>');
        $output->writeln('time: ' . date('Y-m-d H:i:s') . '  tz=' . $engine->timezone());
        $output->writeln('');

        // ---------- 0) 项目 ----------
        if ($projectId <= 0) {
            $row = Db::table('arbitrage_project')->where('status', 1)->orderBy('id')->first();
            if (!$row) {
                $output->writeln('<error>[FATAL] 没有可用矿机项目</error>');
                return self::FAILURE;
            }
            $projectId = (int) $row->id;
        }

        $project = $projectSvc->getActiveProject($projectId);
        if (!$project) {
            $output->writeln("<error>[FATAL] 项目不存在或未上架 project_id={$projectId}</error>");
            return self::FAILURE;
        }

        $output->writeln("[0] project_id={$projectId} name={$project->name} position_cnt={$project->position_cnt}");
        $output->writeln("    min_day_rate={$project->min_day_rate}% max_day_rate={$project->max_day_rate}%");

        // ---------- 1) 行情 ----------
        // 信号池条件：status=有效 + 开赛时间在 [now-1h, now+24h] + 利率 0.4%~10%
        // 未同步时库内旧信号常被 expireStale 标成过期(status=2)，池会为空。
        if ($doSync) {
            $output->writeln('');
            $output->writeln('[1] sync fixtures + ingest signals');
            $output->writeln(sprintf(
                '  fixtures≈%d  signals=%d',
                $engine->syncFixtures(),
                $engine->ingestSignals()
            ));
        } else {
            $output->writeln('');
            $output->writeln('[1] skip sync（未加 --sync）');
        }
        $poolN = count($sigSvc->getAvailablePool());
        $output->writeln("  available_signal_pool={$poolN}");

        if ($poolN <= 0 && !$doSync) {
            $output->writeln('<comment>  [auto] 信号池为空，自动补采一次行情…</comment>');
            try {
                $fxN = $engine->syncFixtures();
                $sgN = $engine->ingestSignals();
                $output->writeln("  fixtures≈{$fxN}  signals={$sgN}");
            } catch (\Throwable $e) {
                $output->writeln('<error>  [error] 自动补采失败: ' . $e->getMessage() . '</error>');
            }
            $poolN = count($sigSvc->getAvailablePool());
            $output->writeln("  available_signal_pool={$poolN}");
        }

        if ($poolN <= 0) {
            $output->writeln('');
            $output->writeln('<error>[FATAL] 当前无可用套利信号，无法开仓。</error>');
            $output->writeln('业务说明：日计划定目标收益率；有 BetBurger 有效信号时才下单，成交后该信号标记已用尽。');
            $output->writeln('未达标时会在后续窗口/补救窗继续等新信号下单，而不是复用已买过的信号。');
            $output->writeln('可先: php webman arbitrage:ingest  再重试本命令。');
            return self::FAILURE;
        }

        // ---------- 2) 投入金额 ----------
        $day = $planSvc->businessDay($engine->timezone());
        if ($amount <= 0) {
            $amount = (new ProjectOrderService())->sumActiveOrderAmount($projectId);
            if ($amount <= 0) {
                $amount = (float) $project->project_price;
            }
        }
        $output->writeln('');
        $output->writeln("[2] target_amount={$amount}");

        // ---------- 3) 日计划（模拟 0 点任务） ----------
        $output->writeln('');
        $output->writeln('[3] ensure day plan (simulate 0:00 job)');
        $plan = $this->ensureTestPlan($planSvc, $engine, $project, $projectId, $day, $amount, $output);
        if (!$plan) {
            return self::FAILURE;
        }

        if ($forceWindow) {
            $schedule = [];
            $count = max(1, (int) $plan->target_trades);
            for ($i = 0; $i < $count; $i++) {
                $schedule[] = time() - 30;
            }
            $planSvc->update((int) $plan->id, [
                'schedule'        => json_encode($schedule, JSON_UNESCAPED_UNICODE),
                'next_idx'        => 0,
                'last_attempt_at' => 0,
                'status'          => DayPlanModel::STATUS_RUNNING,
                'updated_time'    => time(),
            ]);
            $plan = $planSvc->get((int) $plan->id);
            $output->writeln('  [force-window] schedule=' . json_encode($plan->getSchedule(), JSON_UNESCAPED_UNICODE));
        }

        if (!$planSvc->isExecutable($plan)) {
            $output->writeln('<error>[FATAL] 日计划字段不完整，无法执行</error>');
            return self::FAILURE;
        }

        // ---------- 4) 窗口下单 ----------
        $output->writeln('');
        $output->writeln("[4] run order windows (rounds={$rounds})");
        $conf = (array) (config('arbitrage.engine') ?: []);
        $conf['trade_retry_interval_seconds'] = 0;

        for ($i = 1; $i <= $rounds; $i++) {
            $plan = $planSvc->get((int) $plan->id);
            $result = $posSvc->runWindow($plan, $engine, $sigSvc, $conf);
            $output->writeln("  round#{$i}: " . json_encode($result, JSON_UNESCAPED_UNICODE));
            if (in_array(($result['action'] ?? ''), ['done', 'finalize', 'skip_status', 'trades_full', 'skip_invalid_plan'], true)) {
                break;
            }
            if ($forceWindow && ($result['action'] ?? '') === 'opened') {
                $planSvc->update((int) $plan->id, ['last_attempt_at' => 0]);
            }
        }

        // ---------- 5) 汇总 ----------
        $plan = $planSvc->get((int) $plan->id);
        $positions = Db::table('arbitrage_position')
            ->where('project_id', $projectId)
            ->where('plan_id', (int) $plan->id)
            ->orderByDesc('id')
            ->limit(10)
            ->get();
        $attempts = Db::table('arbitrage_attempt')
            ->where('plan_id', (int) $plan->id)
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $output->writeln('');
        $output->writeln('<info>=== summary ===</info>');
        $output->writeln(sprintf(
            'plan: id=%d status=%d realized=%s done=%d/%d',
            (int) $plan->id,
            (int) $plan->status,
            (string) $plan->realized_profit,
            (int) $plan->done_trades,
            (int) $plan->target_trades
        ));
        $output->writeln('positions(' . count($positions) . '):');
        foreach ($positions as $p) {
            $output->writeln(sprintf(
                '  #%d phase=%d stake=%s profit=%s %s vs %s',
                $p->id,
                $p->phase,
                $p->total_stake,
                $p->actual_profit,
                $p->home,
                $p->away
            ));
        }
        $output->writeln('attempts(' . count($attempts) . '):');
        foreach ($attempts as $a) {
            $output->writeln(sprintf(
                '  #%d window=%d status=%s stake=%s',
                $a->id,
                $a->window_idx,
                $a->exec_status,
                $a->stake
            ));
        }

        $opened = 0;
        foreach ($positions as $p) {
            $phase = (int) $p->phase;
            if ($phase >= 1 && $phase <= 3) {
                $opened++;
            }
        }

        if ($opened > 0) {
            $output->writeln('');
            $output->writeln('<info>[OK] 链路通过：日计划已就绪并生成了仓位</info>');
            return self::SUCCESS;
        }

        $output->writeln('');
        $output->writeln('<comment>[WARN] 未生成成功仓位。常见原因：信号池为空 / 模拟失败 / 未加 --force-window</comment>');
        return 2;
    }

    /**
     * 测试用：确保当日有一条可执行日计划（正式环境应由 0 点任务创建）。
     */
    private function ensureTestPlan(
        DayPlanService $planSvc,
        ArbitrageEngine $engine,
        object $project,
        int $projectId,
        string $day,
        float $amount,
        OutputInterface $output
    ): ?DayPlanModel {
        $plan = $planSvc->fetch(['project_id' => $projectId, 'day' => $day]);
        $now = time();
        $tradeCount = max(1, (int) $project->position_cnt);
        $schedule = $engine->generateSchedule($tradeCount);
        $minRate = min((float) $project->min_day_rate, (float) $project->max_day_rate);
        $maxRate = max((float) $project->min_day_rate, (float) $project->max_day_rate);
        $jitter = (crc32($projectId . '|' . $day) % 10000) / 10000.0;
        $targetRate = round(($minRate + ($maxRate - $minRate) * $jitter) / 100.0, 4);
        $targetProfit = round($amount * $targetRate, 2);

        if (!$plan) {
            $plan = $planSvc->create([
                'project_id'      => $projectId,
                'day'             => $day,
                'target_amount'   => round($amount, 2),
                'target_rate'     => $targetRate,
                'target_profit'   => $targetProfit,
                'realized_profit' => 0,
                'target_trades'   => $tradeCount,
                'done_trades'     => 0,
                'schedule'        => json_encode($schedule, JSON_UNESCAPED_UNICODE),
                'next_idx'        => 0,
                'last_attempt_at' => 0,
                'bailout_count'   => 0,
                'created_time'    => $now,
                'updated_time'    => $now,
                'status'          => DayPlanModel::STATUS_PENDING,
            ]);
            $output->writeln("  created plan_id={$plan->id}");
        } else {
            $output->writeln("  existing plan_id={$plan->id}");
        }

        $plan = $planSvc->get((int) $plan->id);
        if (!$plan) {
            $output->writeln('<error>[FATAL] 无法读取日计划</error>');
            return null;
        }

        $output->writeln("  target_rate={$plan->target_rate} target_profit={$plan->target_profit}");
        $output->writeln('  target_trades=' . $plan->target_trades . ' schedule=' . json_encode($plan->getSchedule(), JSON_UNESCAPED_UNICODE));
        return $plan;
    }
}
