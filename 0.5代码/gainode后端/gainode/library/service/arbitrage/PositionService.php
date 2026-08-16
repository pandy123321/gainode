<?php

namespace library\service\arbitrage;

use DateTimeImmutable;
use DateTimeZone;
use library\dao\arbitrage\PositionDao;
use library\model\arbitrage\DayPlanModel;
use library\model\arbitrage\FixtureModel;
use library\model\arbitrage\PositionModel;
use library\model\arbitrage\ProjectOrderModel;
use library\model\arbitrage\SignalModel;
use support\arbitrage\ArbitrageEngine;
use support\arbitrage\math\Money;
use support\arbitrage\math\Simulator;
use support\arbitrage\math\Stake;
use support\extend\Log;
use support\extend\Service;
use support\exception\VerifyException;

/**
 * 矿机项目仓位：窗口下单 / 结算 / 作废
 *
 * @method PositionModel create($data)
 * @method PositionModel update($id, array $data)
 * @method PositionModel get($id, string $field = null)
 * @method PositionModel find($id)
 * @method PositionModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 */
class PositionService extends Service
{
    public function __construct()
    {
        $this->dao = PositionDao::class;
        parent::__construct();
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function runWindows(ArbitrageEngine $engine): array
    {
        $conf = (array) (config('arbitrage.engine') ?: []);
        $planSvc = new DayPlanService();
        $signalSvc = new SignalService();
        $day = $planSvc->businessDay($engine->timezone());

        $results = [];
        foreach ($planSvc->getExecutablePlans($day) as $plan) {
            try {
                // 补偿：重挂时间窗并重置 CLOSED/耗尽计数，再进入单窗逻辑
                if ($planSvc->compensationEnabled()) {
                    $plan = $planSvc->reactivateForCompensation($plan);
                    if ($plan->goalMet()) {
                        $results[] = [
                            'plan_id'    => (int) $plan->id,
                            'project_id' => (int) $plan->project_id,
                            'action'     => 'done',
                            'via'        => 'compensation_already_met',
                        ];
                        continue;
                    }
                }
                $results[] = $this->runWindow($plan, $engine, $signalSvc, $conf);
            } catch (\Throwable $e) {
                Log::channel('library')->error('套利窗口失败', [
                    'plan_id'    => $plan->id,
                    'project_id' => $plan->project_id ?? 0,
                    'msg'        => $e->getMessage(),
                ]);
                $results[] = [
                    'plan_id'    => (int) $plan->id,
                    'project_id' => (int) ($plan->project_id ?? 0),
                    'action'     => 'error',
                    'msg'        => $e->getMessage(),
                ];
            }
        }
        return $results;
    }

    /**
     * @param array<string,mixed> $engineConf
     * @return array<string,mixed>
     */
    public function runWindow(
        DayPlanModel $plan,
        ArbitrageEngine $engine,
        SignalService $signalSvc,
        array $engineConf = []
    ): array {
        $planSvc = new DayPlanService();
        $now = time();
        $windowSec = (int) ($engineConf['trade_window_seconds'] ?? 3600);
        $retrySec = (int) ($engineConf['trade_retry_interval_seconds'] ?? 60);
        $bailoutHour = (int) ($engineConf['bailout_start_hour'] ?? 20);
        $tz = $engine->timezone();
        $projectId = (int) $plan->project_id;

        $plan = $planSvc->get((int) $plan->id) ?: $plan;

        if (!$planSvc->isExecutable($plan)) {
            // 历史脏数据：缺 schedule 时自动补窗口，避免永久 skip_invalid_plan
            if ($plan->getSchedule() === []
                && (int) $plan->project_id > 0
                && (float) $plan->target_amount > 0
                && (float) $plan->target_profit > 0
                && (int) $plan->target_trades > 0) {
                $plan = $planSvc->ensureSchedule($plan);
            }
            if (!$planSvc->isExecutable($plan)) {
                return [
                    'plan_id'    => (int) $plan->id,
                    'project_id' => $projectId,
                    'action'     => 'skip_invalid_plan',
                ];
            }
        }

        if ((int) $plan->status === DayPlanModel::STATUS_CLOSED) {
            if ($planSvc->compensationEnabled()) {
                $plan = $planSvc->reactivateForCompensation($plan);
                return [
                    'plan_id'       => (int) $plan->id,
                    'project_id'    => $projectId,
                    'action'        => 'compensation_rescue',
                    'bailout_count' => (int) $plan->bailout_count,
                    'next_idx'      => (int) $plan->next_idx,
                    'schedule_len'  => count($plan->getSchedule()),
                ];
            }
            if ($planSvc->canBailout($plan)) {
                $plan = $planSvc->addBailoutWindows($plan);
                return [
                    'plan_id'       => (int) $plan->id,
                    'project_id'    => $projectId,
                    'action'        => 'bailout_rescue',
                    'bailout_count' => (int) $plan->bailout_count,
                    'next_idx'      => (int) $plan->next_idx,
                    'schedule_len'  => count($plan->getSchedule()),
                ];
            }
            if ($planSvc->canSettleRedeploy($plan)
                && $this->sumPlanStaked((int) $plan->id) + 10 <= (float) $plan->target_amount + 1e-9) {
                $plan = $planSvc->addSettleRedeployWindows($plan);
                return [
                    'plan_id'       => (int) $plan->id,
                    'project_id'    => $projectId,
                    'action'        => 'settle_redeploy_rescue',
                    'bailout_count' => (int) $plan->bailout_count,
                    'next_idx'      => (int) $plan->next_idx,
                    'schedule_len'  => count($plan->getSchedule()),
                ];
            }
        }

        if (!in_array((int) $plan->status, [DayPlanModel::STATUS_PENDING, DayPlanModel::STATUS_RUNNING], true)) {
            return ['plan_id' => (int) $plan->id, 'project_id' => $projectId, 'action' => 'skip_status'];
        }

        // 已达目标收益率 → status=3，后续调度不再执行
        if ($plan->goalMet()) {
            $planSvc->markDone($plan);
            return ['plan_id' => (int) $plan->id, 'project_id' => $projectId, 'action' => 'done'];
        }

        // 计划目标利率须在项目 min~max 日内收益区间内（补偿模式可跳过）
        if (!$planSvc->isPlanRateValid($plan)
            && !( $planSvc->compensationEnabled() && !empty(config('arbitrage.engine.compensation.skip_rate_check')) )
        ) {
            return [
                'plan_id'    => (int) $plan->id,
                'project_id' => $projectId,
                'action'     => 'skip_invalid_rate',
                'msg'        => '计划目标收益率不在项目允许区间内',
            ];
        }

        // target_trades 只是预估窗口数；未达 target_profit 就继续下单/补救
        // 本金被 LOCKED/PENDING 仓位占满时不烧窗、不 finalize→4，等结算释放后再交易

        $schedule = $plan->getSchedule();
        $nextIdx = (int) $plan->next_idx;
        $hour = (int) (new DateTimeImmutable('now', new DateTimeZone($tz)))->format('G');
        $minStake = 10.0;
        $planId = (int) $plan->id;
        $alreadyStaked = $this->sumPlanStaked($planId);
        $remainingCapital = Money::round2((float) $plan->target_amount - $alreadyStaked);
        $openPositions = $this->countOpenPositions($planId);

        // 有未结仓位且可下单本金不足 → 等待结算（不推进 next_idx、不写 attempt）
        if ($openPositions > 0 && $remainingCapital < $minStake) {
            if ((int) $plan->status !== DayPlanModel::STATUS_RUNNING) {
                $planSvc->update($planId, [
                    'status'       => DayPlanModel::STATUS_RUNNING,
                    'updated_time' => $now,
                ]);
            }
            return [
                'plan_id'         => $planId,
                'project_id'      => $projectId,
                'action'          => 'wait_settle',
                'open_positions'  => $openPositions,
                'already_staked'  => $alreadyStaked,
                'remaining'       => $remainingCapital,
                'shortfall'       => round(max(0, (float) $plan->target_profit - (float) $plan->realized_profit), 2),
            ];
        }

        if ($nextIdx >= count($schedule)) {
            // 仍有未结仓位：绝不能 CLOSED
            if ($openPositions > 0) {
                return [
                    'plan_id'        => $planId,
                    'project_id'     => $projectId,
                    'action'         => 'wait_settle',
                    'open_positions' => $openPositions,
                    'reason'         => 'schedule_exhausted_but_open',
                    'shortfall'      => round(max(0, (float) $plan->target_profit - (float) $plan->realized_profit), 2),
                ];
            }

            if ($planSvc->canBailout($plan)) {
                // 有可用本金：立刻追加补救窗（结算复用不必干等 bailout_start_hour）
                if ($remainingCapital >= $minStake || $hour >= $bailoutHour) {
                    $plan = $planSvc->addBailoutWindows($plan);
                    return [
                        'plan_id'       => (int) $plan->id,
                        'project_id'    => $projectId,
                        'action'        => 'bailout_added',
                        'bailout_count' => (int) $plan->bailout_count,
                        'remaining'     => $remainingCapital,
                        'immediate'     => $remainingCapital >= $minStake,
                    ];
                }
                if ((int) $plan->status !== DayPlanModel::STATUS_RUNNING) {
                    $planSvc->update($planId, [
                        'status'       => DayPlanModel::STATUS_RUNNING,
                        'updated_time' => $now,
                    ]);
                }
                return [
                    'plan_id'      => $planId,
                    'project_id'   => $projectId,
                    'action'       => 'wait_bailout',
                    'bailout_hour' => $bailoutHour,
                    'current_hour' => $hour,
                    'shortfall'    => round((float) $plan->target_profit - (float) $plan->realized_profit, 2),
                ];
            }

            if ($plan->goalMet()) {
                $planSvc->markDone($plan);
                $finalStatus = DayPlanModel::STATUS_DONE;
            } elseif ($remainingCapital >= $minStake && $planSvc->canSettleRedeploy($plan)) {
                // 常规补救/补偿：本金已释放仍未达标 → 继续挂窗
                $plan = $planSvc->addSettleRedeployWindows($plan);
                return [
                    'plan_id'       => (int) $plan->id,
                    'project_id'    => $projectId,
                    'action'        => 'settle_redeploy',
                    'bailout_count' => (int) $plan->bailout_count,
                    'remaining'     => $remainingCapital,
                ];
            } elseif ($planSvc->compensationNeverClose()) {
                // 补偿模式：本金暂时不足也保留 RUNNING，等结算或信号，绝不 CLOSED
                if ((int) $plan->status !== DayPlanModel::STATUS_RUNNING) {
                    $planSvc->update($planId, [
                        'status'       => DayPlanModel::STATUS_RUNNING,
                        'updated_time' => $now,
                    ]);
                }
                return [
                    'plan_id'         => $planId,
                    'project_id'      => $projectId,
                    'action'          => 'wait_compensation',
                    'remaining'       => $remainingCapital,
                    'open_positions'  => $openPositions,
                    'shortfall'       => round(max(0, (float) $plan->target_profit - (float) $plan->realized_profit), 2),
                ];
            } else {
                $planSvc->update($planId, [
                    'status'       => DayPlanModel::STATUS_CLOSED,
                    'updated_time' => $now,
                ]);
                $finalStatus = DayPlanModel::STATUS_CLOSED;
            }
            return [
                'plan_id'    => $planId,
                'project_id' => $projectId,
                'action'     => 'finalize',
                'status'     => $finalStatus ?? DayPlanModel::STATUS_CLOSED,
                'shortfall'  => round(max(0, (float) $plan->target_profit - (float) $plan->realized_profit), 2),
            ];
        }

        $windowAt = (int) $schedule[$nextIdx];
        if ($now < $windowAt) {
            return ['plan_id' => $planId, 'project_id' => $projectId, 'action' => 'wait', 'window_at' => $windowAt];
        }
        // 锁仓待结算期间不推进 next_idx，避免空烧 schedule / bailout
        if ($now > $windowAt + $windowSec) {
            if ($openPositions > 0 && $remainingCapital < $minStake) {
                return [
                    'plan_id'        => $planId,
                    'project_id'     => $projectId,
                    'action'         => 'wait_settle',
                    'open_positions' => $openPositions,
                    'reason'         => 'window_expired_hold',
                ];
            }
            $planSvc->update($planId, [
                'next_idx'     => $nextIdx + 1,
                'status'       => DayPlanModel::STATUS_RUNNING,
                'updated_time' => $now,
            ]);
            return ['plan_id' => $planId, 'project_id' => $projectId, 'action' => 'window_expired', 'next_idx' => $nextIdx + 1];
        }

        if ((int) $plan->last_attempt_at > 0 && ($now - (int) $plan->last_attempt_at) < $retrySec) {
            return ['plan_id' => $planId, 'project_id' => $projectId, 'action' => 'cooldown'];
        }

        $remainingWindows = Stake::remainingAttempts([
            'schedule'      => $schedule,
            'next_idx'      => $nextIdx,
            'target_trades' => (int) $plan->target_trades,
            'done_trades'   => (int) $plan->done_trades,
        ]);
        $pool = $signalSvc->getAvailablePool();
        $shortfallProfit = Money::round2(max(0, (float) $plan->target_profit - (float) $plan->realized_profit));
        $selected = $signalSvc->selectSignal($pool, $remainingWindows, [
            'prefer_high'      => true,
            'target_rate'      => (float) $plan->target_rate,
            'shortfall_profit' => $shortfallProfit,
            'target_amount'    => (float) $plan->target_amount,
        ]);

        $planSvc->update($planId, [
            'last_attempt_at' => $now,
            'status'          => DayPlanModel::STATUS_RUNNING,
            'updated_time'    => $now,
        ]);

        if ($selected === null) {
            (new AttemptService())->record(
                $projectId,
                $planId,
                0,
                0,
                $nextIdx,
                'signal_gone',
                0,
                0,
                ['reason' => 'no_signal', 'pool_size' => count($pool)]
            );
            return [
                'plan_id'    => $planId,
                'project_id' => $projectId,
                'action'     => 'no_signal',
                'pool_size'  => count($pool),
                'window_idx' => $nextIdx,
            ];
        }

        $live = $signalSvc->resolveLiveSignal($engine, $selected);
        if ($live === null) {
            (new AttemptService())->record(
                $projectId,
                $planId,
                (int) $selected->id,
                (int) $selected->fixture_id,
                $nextIdx,
                'signal_gone',
                0,
                (float) $selected->profit_rate,
                ['reason' => 'live_missing']
            );
            $planSvc->update($planId, [
                'next_idx'     => $nextIdx + 1,
                'updated_time' => $now,
            ]);
            return ['plan_id' => $planId, 'project_id' => $projectId, 'action' => 'signal_gone'];
        }

        $live['id'] = (int) $selected->id;
        $live['fixture_id'] = (int) ($live['fixture_id'] ?? $selected->fixture_id);
        $live['event_id'] = (int) ($live['event_id'] ?? $selected->event_id);

        $maxProfit = $planSvc->maxAllowedProfit($plan);
        $goalProfit = min((float) $plan->target_profit, $maxProfit);
        $planArr = [
            'target_profit'   => $goalProfit,
            'realized_profit' => (float) $plan->realized_profit,
            'target_amount'   => (float) $plan->target_amount,
            'already_staked'  => $alreadyStaked,
            'target_trades'   => (int) $plan->target_trades,
            'done_trades'     => (int) $plan->done_trades,
            'schedule'        => $schedule,
            'next_idx'        => $nextIdx,
        ];
        $stake = Stake::calculate($planArr, (float) ($live['profit_rate'] ?? $selected->profit_rate));
        if ($stake < $minStake) {
            if ($this->countOpenPositions($planId) > 0) {
                return [
                    'plan_id'        => $planId,
                    'project_id'     => $projectId,
                    'action'         => 'wait_settle',
                    'already_staked' => $alreadyStaked,
                    'target_amount'  => (float) $plan->target_amount,
                    'reason'         => 'capital_locked',
                ];
            }
            (new AttemptService())->record(
                $projectId,
                $planId,
                (int) $selected->id,
                (int) $live['fixture_id'],
                $nextIdx,
                'limited',
                0,
                (float) ($live['profit_rate'] ?? 0),
                ['reason' => 'insufficient_remaining_capital', 'already_staked' => $alreadyStaked]
            );
            return [
                'plan_id'         => $planId,
                'project_id'      => $projectId,
                'action'          => 'capital_exhausted',
                'already_staked'  => $alreadyStaked,
                'target_amount'   => (float) $plan->target_amount,
            ];
        }
        $sim = Simulator::execute($planArr, $live, $stake);

        (new AttemptService())->record(
            $projectId,
            (int) $plan->id,
            (int) $selected->id,
            (int) $live['fixture_id'],
            $nextIdx,
            (string) $sim['exec_status'],
            $stake,
            (float) ($live['profit_rate'] ?? 0),
            $sim['detail'] ?? []
        );

        if (empty($sim['success'])) {
            $planSvc->update((int) $plan->id, [
                'next_idx'     => $nextIdx + 1,
                'updated_time' => $now,
            ]);
            return [
                'plan_id'     => (int) $plan->id,
                'project_id'  => $projectId,
                'action'      => 'attempt_failed',
                'exec_status' => $sim['exec_status'],
            ];
        }

        // 原子占用信号：未结算不可复买；并发下只有一个成功
        if (!$signalSvc->tryClaimSignal((int) $selected->id)) {
            (new AttemptService())->record(
                $projectId,
                (int) $plan->id,
                (int) $selected->id,
                (int) $live['fixture_id'],
                $nextIdx,
                'signal_gone',
                $stake,
                (float) ($live['profit_rate'] ?? 0),
                ['reason' => 'signal_already_claimed']
            );
            return [
                'plan_id'    => (int) $plan->id,
                'project_id' => $projectId,
                'action'     => 'signal_claimed',
                'signal_id'  => (int) $selected->id,
            ];
        }

        try {
            $position = $this->openPosition($plan, $selected, $live, $sim, $stake);
        } catch (\Throwable $e) {
            $signalSvc->releaseClaim((int) $selected->id);
            Log::channel('library')->error('开仓失败', [
                'plan_id'    => $plan->id,
                'project_id' => $projectId,
                'msg'        => $e->getMessage(),
            ]);
            $planSvc->update((int) $plan->id, [
                'next_idx'     => $nextIdx + 1,
                'updated_time' => $now,
            ]);
            return [
                'plan_id'    => (int) $plan->id,
                'project_id' => $projectId,
                'action'     => 'open_failed',
                'msg'        => $e->getMessage(),
            ];
        }

        // 累计利润不得超过项目最高日收益率对应利润（区间内有效）
        $maxProfit = $planSvc->maxAllowedProfit($plan);
        $rawProfit = Money::round2((float) $sim['actual_profit']);
        $room = Money::round2(max(0, $maxProfit - (float) $plan->realized_profit));
        $creditedProfit = min($rawProfit, $room);
        if ($creditedProfit < $rawProfit && (int) $position->id > 0) {
            $this->update((int) $position->id, [
                'actual_profit' => $creditedProfit,
                'actual_rate'   => $stake > 0 ? round($creditedProfit / $stake, 4) : 0,
                'updated_time'  => $now,
            ]);
            $sim['actual_profit'] = $creditedProfit;
        }

        $doneProfit = Money::round2((float) $plan->realized_profit + $creditedProfit);
        $doneTrades = (int) $plan->done_trades + 1;
        $reached = (float) $plan->target_profit > 0
            && $doneProfit >= Money::round2((float) $plan->target_profit);

        $planSvc->update((int) $plan->id, [
            'realized_profit' => $doneProfit,
            'done_trades'     => $doneTrades,
            'next_idx'        => $nextIdx + 1,
            'status'          => $reached ? DayPlanModel::STATUS_DONE : DayPlanModel::STATUS_RUNNING,
            'updated_time'    => $now,
        ]);

        return [
            'plan_id'     => (int) $plan->id,
            'project_id'  => $projectId,
            'action'      => $reached ? 'done' : 'opened',
            'position_id' => (int) $position->id,
            'profit'      => $creditedProfit,
            'stake'       => $stake,
            'shortfall'   => round(max(0, (float) $plan->target_profit - $doneProfit), 2),
            'status'      => $reached ? DayPlanModel::STATUS_DONE : DayPlanModel::STATUS_RUNNING,
            'exec_status' => $sim['exec_status'],
        ];
    }

    /**
     * 本计划已锁仓本金：仅 LOCKED / PENDING_SETTLE 占用 target_amount。
     * 结算(SETTLED)或作废(VOIDED)后释放，才可继续使用该资金。
     */
    public function sumPlanStaked(int $planId): float
    {
        if ($planId <= 0) {
            return 0.0;
        }
        $sum = (float) \support\Db::table('arbitrage_position')
            ->where('plan_id', $planId)
            ->whereIn('phase', [
                PositionModel::PHASE_LOCKED,
                PositionModel::PHASE_PENDING_SETTLE,
            ])
            ->sum('total_stake');
        return Money::round2($sum);
    }

    /**
     * 未释放本金的仓位数量（锁仓中 / 待结算）。
     */
    public function countOpenPositions(int $planId): int
    {
        if ($planId <= 0) {
            return 0;
        }
        return (int) \support\Db::table('arbitrage_position')
            ->where('plan_id', $planId)
            ->whereIn('phase', [
                PositionModel::PHASE_LOCKED,
                PositionModel::PHASE_PENDING_SETTLE,
            ])
            ->count();
    }

    /**
     * @param array<string,mixed> $live
     * @param array<string,mixed> $sim
     */
    public function openPosition(
        DayPlanModel $plan,
        SignalModel $signal,
        array $live,
        array $sim,
        float $totalStake
    ): PositionModel {
        $fixtureSvc = new FixtureService();
        $fixtureId = (int) ($live['fixture_id'] ?? $signal->fixture_id);
        $fx = $fixtureId > 0 ? $fixtureSvc->get($fixtureId) : null;

        $totalStake = round($totalStake, 2);
        if ($totalStake < 10) {
            throw new VerifyException('注额过低');
        }

        $signalId = (int) $signal->id;
        if ($signalId > 0) {
            $busy = (int) \support\Db::table('arbitrage_position')
                ->where('signal_id', $signalId)
                ->whereIn('phase', [
                    PositionModel::PHASE_LOCKED,
                    PositionModel::PHASE_PENDING_SETTLE,
                ])
                ->count();
            if ($busy > 0) {
                throw new VerifyException('该信号已有未结算仓位，不可重复买入');
            }
        }

        $already = $this->sumPlanStaked((int) $plan->id);
        $budget = (float) $plan->target_amount;
        if ($budget > 0 && Money::round2($already + $totalStake) > $budget + 0.01) {
            throw new VerifyException('注额超过当日剩余可投本金');
        }

        $now = time();
        $expectedRate = (float) ($live['profit_rate'] ?? $signal->profit_rate);
        $actualRate = (float) ($sim['actual_rate'] ?? $expectedRate);
        $actualProfit = (float) ($sim['actual_profit'] ?? 0);

        $position = $this->create([
            'project_id'        => (int) $plan->project_id,
            'plan_id'           => (int) $plan->id,
            'signal_id'         => (int) $signal->id,
            'fixture_id'        => $fixtureId,
            'event_id'          => (int) ($live['event_id'] ?? $signal->event_id),
            'event_name'        => (string) ($live['event_name'] ?? $signal->event_name),
            'league'            => (string) ($fx->league ?? $live['league'] ?? ''),
            'home'              => (string) ($fx->home ?? $live['home'] ?? ''),
            'away'              => (string) ($fx->away ?? $live['away'] ?? ''),
            'kickoff_at'        => (int) ($fx->kickoff_at ?? $live['started_at'] ?? $signal->started_at),
            'leg1_bookmaker'    => (string) ($live['leg1_bookmaker'] ?? $signal->leg1_bookmaker),
            'leg1_bookmaker_id' => (int) ($live['leg1_bookmaker_id'] ?? $signal->leg1_bookmaker_id) ?: null,
            'leg1_market'       => (string) ($live['leg1_market'] ?? $signal->leg1_market),
            'leg1_odds'         => round((float) ($sim['leg1_odds'] ?? $live['leg1_odds'] ?? $signal->leg1_odds), 2),
            'leg1_stake'        => round((float) ($sim['leg1_stake'] ?? 0), 2),
            'leg2_bookmaker'    => (string) ($live['leg2_bookmaker'] ?? $signal->leg2_bookmaker),
            'leg2_bookmaker_id' => (int) ($live['leg2_bookmaker_id'] ?? $signal->leg2_bookmaker_id) ?: null,
            'leg2_market'       => (string) ($live['leg2_market'] ?? $signal->leg2_market),
            'leg2_odds'         => round((float) ($sim['leg2_odds'] ?? $live['leg2_odds'] ?? $signal->leg2_odds), 2),
            'leg2_stake'        => round((float) ($sim['leg2_stake'] ?? 0), 2),
            'total_stake'       => round($totalStake, 2),
            'expected_rate'     => round($expectedRate, 4),
            'expected_profit'   => Money::round2($totalStake * $expectedRate),
            'actual_rate'       => round($actualRate, 4),
            'actual_profit'     => Money::round2($actualProfit),
            'phase'             => PositionModel::PHASE_LOCKED,
            'locked_at'         => $now,
            'settled_at'        => 0,
            'voided_at'         => null,
            'void_reason'       => '',
            'created_time'      => $now,
            'updated_time'      => $now,
            'status'            => 1,
        ]);

        return $position;
    }

    public function settlePositions(ArbitrageEngine $engine): int
    {
        $n = 0;
        $n += $this->settleFinishedPositions();
        $n += $this->voidCancelledPositions();
        return $n;
    }

    public function settleFinishedPositions(): int
    {
        $rows = $this->fetchAll([
            'phase' => ['in', [PositionModel::PHASE_LOCKED, PositionModel::PHASE_PENDING_SETTLE]],
            'size'  => 500,
        ], ['id' => 'asc']);
        $fixtureSvc = new FixtureService();
        $n = 0;
        foreach ($rows as $pos) {
            $fx = $fixtureSvc->get((int) $pos->fixture_id);
            if (!$fx) {
                continue;
            }
            // 业务要求：比赛结束(is_finished=1)即自动结算仓位
            if ((int) $fx->is_finished !== FixtureModel::FINISHED && !$fixtureSvc->isSettledReady($fx)) {
                if ($fx && (int) $fx->is_finished === FixtureModel::FINISHED
                    && (int) $fx->is_placeholder === FixtureModel::NOT_PLACEHOLDER
                    && (int) $pos->phase === PositionModel::PHASE_LOCKED) {
                    $this->update((int) $pos->id, [
                        'phase'        => PositionModel::PHASE_PENDING_SETTLE,
                        'updated_time' => time(),
                    ]);
                }
                continue;
            }
            try {
                if ($this->creditSettle($pos)) {
                    $n++;
                }
            } catch (\Throwable $e) {
                Log::channel('library')->error('仓位结算失败', [
                    'position_id' => $pos->id,
                    'project_id'  => $pos->project_id,
                    'msg'         => $e->getMessage(),
                ]);
            }
        }
        return $n;
    }

    public function voidCancelledPositions(): int
    {
        $rows = $this->fetchAll([
            'phase' => ['in', [PositionModel::PHASE_LOCKED, PositionModel::PHASE_PENDING_SETTLE]],
            'size'  => 500,
        ]);
        $fixtureSvc = new FixtureService();
        $planSvc = new DayPlanService();
        $n = 0;
        $now = time();

        foreach ($rows as $pos) {
            $fx = $fixtureSvc->get((int) $pos->fixture_id);
            if (!$fx || !$fixtureSvc->isVoidStatus((string) $fx->status_short)) {
                continue;
            }
            $reason = match (strtoupper((string) $fx->status_short)) {
                'CANC' => 'fixture_match_cancelled',
                'ABD'  => 'fixture_match_abandoned',
                'PST'  => 'fixture_match_postponed',
                default => 'fixture_void',
            };

            try {
                $this->update((int) $pos->id, [
                    'phase'        => PositionModel::PHASE_VOIDED,
                    'voided_at'    => $now,
                    'void_reason'  => $reason,
                    'updated_time' => $now,
                ]);

                $plan = $planSvc->get((int) $pos->plan_id);
                if ($plan) {
                    $realized = Money::round2(max(0, (float) $plan->realized_profit - (float) $pos->actual_profit));
                    $done = max(0, (int) $plan->done_trades - 1);
                    $planSvc->update((int) $plan->id, [
                        'realized_profit' => $realized,
                        'done_trades'     => $done,
                        'updated_time'    => $now,
                    ]);
                }
                $n++;
            } catch (\Throwable $e) {
                Log::channel('library')->error('仓位作废失败', [
                    'position_id' => $pos->id,
                    'msg'         => $e->getMessage(),
                ]);
            }
        }
        return $n;
    }

    private function creditSettle(PositionModel $pos): bool
    {
        if ((int) $pos->phase === PositionModel::PHASE_SETTLED) {
            return false;
        }
        $now = time();
        $pos->saveData([
            'phase'        => PositionModel::PHASE_SETTLED,
            'settled_at'   => $now,
            'status' => 1,  //等待处理订单明细
        ]);
        $plan = (new DayPlanService())->get((int) $pos->plan_id);
        if ($plan) {
            // 订单日志由外部自行处理时，不应反向影响仓位结算成功
            try {
                (new ProjectOrderService())->allocatePositionIncome($plan, $pos);
            } catch (\Throwable $e) {
                Log::channel('library')->error('仓位已结算，但订单日志处理失败', [
                    'position_id' => (int) $pos->id,
                    'plan_id'     => (int) $pos->plan_id,
                    'msg'         => $e->getMessage(),
                ]);
            }
        }
        return true;
    }
}
