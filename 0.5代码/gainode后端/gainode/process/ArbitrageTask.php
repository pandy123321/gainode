<?php

namespace process;

use library\model\arbitrage\PositionModel;
use library\service\arbitrage\PositionService;
use library\service\arbitrage\ProjectOrderService;
use support\arbitrage\ArbitrageEngine;
use support\extend\Log;
use support\extend\Redis;
use Workerman\Timer;

/**
 * 套利常驻进程：信号采集 / 比赛同步 / 窗口下单 / 结算
 * 日计划由业务侧每日 0 点创建，本进程不重建计划。
 *
 * 启用条件：.env 的 APP_PROCESS_LIST 需包含 arb_task
 */
class ArbitrageTask
{
    public function onWorkerStart()
    {
        $engine = new ArbitrageEngine();
        $conf = config('arbitrage.engine') ?: [];
        $signalPoll  = max(10, (int) ($conf['signal_poll_seconds'] ?? 60));
        $fixturePoll = max(30, (int) ($conf['fixture_poll_seconds'] ?? 120));
        $orderPoll   = max(10, (int) ($conf['order_poll_seconds'] ?? 30));
        $settlePoll  = max(30, (int) ($conf['settle_poll_seconds'] ?? 60));
        $positionSync  = max(30, (int) ($conf['position_sync_seconds'] ?? 60));

        Log::info('arb_task 进程已启动', [
            'signal_poll'  => $signalPoll,
            'fixture_poll' => $fixturePoll,
            'order_poll'   => $orderPoll,
            'settle_poll'  => $settlePoll,
            'position_sync'  => $positionSync,
        ]);

        // 启动后 2 秒先跑一轮采集+同步，避免干等首个 interval
        Timer::add(2, function () use ($engine) {
            $this->guarded('ingestSignals', $engine);
            $this->guarded('syncFixtures', $engine);
        }, [], false);

        Timer::add($signalPoll,  fn() => $this->guarded('ingestSignals', $engine));
        Timer::add($fixturePoll, fn() => $this->guarded('syncFixtures', $engine));
        Timer::add($orderPoll,   fn() => $this->guarded('runOrderGeneration', $engine));
        Timer::add($settlePoll,  fn() => $this->guarded('settle', $engine));
        Timer::add($positionSync,  fn() => $this->positionSync($engine));
    }

    private function guarded(string $method, ArbitrageEngine $engine): void
    {
        $lock = 'arb_task:' . $method;
        // TTL 略大于常见耗时，防止异常未解锁时死锁；正常路径 finally 会 del
        $ttl = 120;
        try {
            if (!Redis::addLock($lock, $ttl)) {
                Log::info("arb_task {$method} 跳过(锁占用中)");
                return;
            }
        } catch (\Throwable $e) {
            Log::error("arb_task {$method} 加锁失败: " . $e->getMessage());
            return;
        }

        $started = microtime(true);
        try {
            $result = $engine->$method();
            Log::info("arb_task {$method} 完成", [
                'result' => $result,
                'ms'     => (int) ((microtime(true) - $started) * 1000),
            ]);
        } catch (\Throwable $e) {
            Log::error("arb_task {$method} 异常: " . $e->getMessage());
        } finally {
            try {
                Redis::del($lock);
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    private function positionSync(ArbitrageEngine $engine): void
    {
        $positionSvc = new PositionService();
        $projectOrderSvc = new ProjectOrderService();
        try {
            // phase=已结算 且 status=待处理(1) 才分账
            $selector = $positionSvc->selector([
                'phase'  => PositionModel::PHASE_SETTLED,
                'status' => PositionModel::STATUS_PENDING,
            ], ['id' => 'asc']);
            $selector->chunk(50, function ($rows) use ($projectOrderSvc, $positionSvc) {
                foreach ($rows as $obj) {
                    try {
                        $plan = $obj->plan
                            ?: (new \library\service\arbitrage\DayPlanService())->get((int) $obj->plan_id);
                        if (empty($plan)) {
                            Log::error('arb_task syncPositions 找不到日计划', ['position_id' => $obj->id]);
                            continue;
                        }
                        $res = $projectOrderSvc->allocatePositionIncome($plan, $obj);
                        if (!empty($res)) {
                            $positionSvc->update((int) $obj->id, [
                                'status'       => 2,
                                'updated_time' => time(),
                            ]);
                            Log::info('arb_task syncPositions 持仓利润分账成功', ['position_id' => $obj->id]);
                        } else {
                            Log::error('arb_task syncPositions 持仓利润同步失败', ['position_id' => $obj->id]);
                        }
                    } catch (\Throwable $e) {
                        Log::error('arb_task syncPositions 单条异常: ' . $e->getMessage(), [
                            'position_id' => $obj->id ?? 0,
                        ]);
                    }
                }
            });
        } catch (\Throwable $e) {
            Log::error('arb_task syncPositions 异常: ' . $e->getMessage());
        }
    }
}
