<?php

namespace library\service\arbitrage;

use library\dao\arbitrage\SignalDao;
use library\model\arbitrage\FixtureModel;
use library\model\arbitrage\PositionModel;
use library\model\arbitrage\SignalModel;
use support\arbitrage\ArbitrageEngine;
use support\extend\Log;
use support\extend\Service;

/**
 * 套利信号采集 / 过期 / 选池
 *
 * @method SignalModel create($data)
 * @method SignalModel updateOrCreate(array $params, array $data)
 * @method SignalModel update($id, array $data)
 * @method SignalModel get($id, string $field = null)
 * @method SignalModel find($id)
 * @method SignalModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 */
class SignalService extends Service
{
    public function __construct()
    {
        $this->dao = SignalDao::class;
        parent::__construct();
    }

    /**
     * 拉取 BetBurger 信号并幂等入库。
     */
    public function ingest(ArbitrageEngine $engine, float $previewStake = 10000.0): int
    {
        try {
            $signals = $engine->betBurger()->fetchSignals($previewStake);
        } catch (\Throwable $e) {
            Log::channel('library')->error('套利信号采集失败(BetBurger)', ['msg' => $e->getMessage()]);
            return 0;
        }
        $n = $this->importSignals($signals, $engine);
        $this->expireStale();
        return $n;
    }

    /**
     * @param list<array<string,mixed>> $rawSignals
     */
    public function importSignals(array $rawSignals, ?ArbitrageEngine $engine = null): int
    {
        $now = time();
        $count = 0;
        $fixtureService = new FixtureService();
        $rawService = new SignalRawService();
        $pool = $fixtureService->loadMatchPool();
        $matcher = $engine?->fixtureMatcher();

        foreach ($rawSignals as $s) {
            $eventId = (int) ($s['event_id'] ?? 0);
            $arbHash = (string) ($s['arb_hash'] ?? '');
            if ($eventId <= 0 || $arbHash === '') {
                continue;
            }

            $valid = self::isValidArbitrage($s);
            $fixtureId = $fixtureService->ensureFixtureForSignal($s, $pool, $matcher);

            $data = [
                'event_id'          => $eventId,
                'fixture_id'        => $fixtureId,
                'arb_hash'          => $arbHash,
                'event_name'        => (string) ($s['event_name'] ?? ''),
                'is_live'           => !empty($s['is_live']) ? 1 : 0,
                'started_at'        => (int) ($s['started_at'] ?? 0),
                'betburger_pct'     => round((float) ($s['betburger_pct'] ?? 0), 2),
                'profit_rate'       => round((float) ($s['profit_rate'] ?? 0), 4),
                'leg1_bookmaker_id' => (int) ($s['leg1_bookmaker_id'] ?? $s['leg1']['bookmaker_id'] ?? 0) ?: null,
                'leg1_bookmaker'    => (string) ($s['leg1_bookmaker'] ?? $s['leg1']['bookmaker'] ?? ''),
                'leg1_market'       => (string) ($s['leg1_market'] ?? $s['leg1']['market'] ?? ''),
                'leg1_odds'         => round((float) ($s['leg1_odds'] ?? $s['leg1']['odds'] ?? 0), 2),
                'leg1_market_param' => isset($s['leg1_market_param']) || isset($s['leg1']['market_param'])
                    ? (float) ($s['leg1_market_param'] ?? $s['leg1']['market_param'])
                    : null,
                'leg1_market_type'  => (int) ($s['leg1_market_type'] ?? $s['leg1']['market_type'] ?? 0) ?: null,
                'leg2_bookmaker_id' => (int) ($s['leg2_bookmaker_id'] ?? $s['leg2']['bookmaker_id'] ?? 0) ?: null,
                'leg2_bookmaker'    => (string) ($s['leg2_bookmaker'] ?? $s['leg2']['bookmaker'] ?? ''),
                'leg2_market'       => (string) ($s['leg2_market'] ?? $s['leg2']['market'] ?? ''),
                'leg2_odds'         => round((float) ($s['leg2_odds'] ?? $s['leg2']['odds'] ?? 0), 2),
                'leg2_market_param' => isset($s['leg2_market_param']) || isset($s['leg2']['market_param'])
                    ? (float) ($s['leg2_market_param'] ?? $s['leg2']['market_param'])
                    : null,
                'leg2_market_type'  => (int) ($s['leg2_market_type'] ?? $s['leg2']['market_type'] ?? 0) ?: null,
                'preview_stake'     => round((float) ($s['preview_stake'] ?? 0), 2),
                'current_score'     => (string) ($s['current_score'] ?? '') ?: null,
                'last_seen_at'      => $now,
                'updated_time'      => $now,
                'status'            => $valid ? SignalModel::STATUS_VALID : SignalModel::STATUS_INVALID,
            ];

            $existing = $this->fetch(['event_id' => $eventId, 'arb_hash' => $arbHash]);
            if ($existing) {
                if (!$valid) {
                    $data['status'] = SignalModel::STATUS_INVALID;
                } elseif (in_array((int) $existing->status, [
                    SignalModel::STATUS_USED,
                    SignalModel::STATUS_CLOSED,
                    SignalModel::STATUS_DELETED,
                ], true)) {
                    // 已买过的套利机会不再复活为可选；只刷新赔率快照字段
                    unset($data['status']);
                } else {
                    $data['status'] = SignalModel::STATUS_VALID;
                }
                $this->update((int) $existing->id, $data);
                $signalId = (int) $existing->id;
            } else {
                $data['first_seen_at'] = $now;
                $data['created_time'] = $now;
                $created = $this->create($data);
                $signalId = (int) ($created->id ?? 0);
            }

            if ($signalId > 0) {
                $rawService->upsertPayload($signalId, $s['raw'] ?? $s);
            }
            $count++;
        }

        Log::channel('library')->info('套利信号采集完成', ['imported' => $count]);
        return $count;
    }

    /**
     * 过期：开赛超过 2h，或 1h 未见。
     */
    public function expireStale(): int
    {
        $now = time();
        $conn = $this->connection();
        $n = 0;
        try {
            $n += $conn->table('arbitrage_signal')
                ->where('status', SignalModel::STATUS_VALID)
                ->where('started_at', '>', 0)
                ->where('started_at', '<', $now - 7200)
                ->update(['status' => SignalModel::STATUS_EXPIRED, 'updated_time' => $now]);

            $n += $conn->table('arbitrage_signal')
                ->where('status', SignalModel::STATUS_VALID)
                ->where('last_seen_at', '<', $now - 3600)
                ->update(['status' => SignalModel::STATUS_EXPIRED, 'updated_time' => $now]);
        } catch (\Throwable $e) {
            Log::channel('library')->error('信号过期清理失败: ' . $e->getMessage());
        }
        return (int) $n;
    }

    /**
     * 可选信号池：仅 VALID，且没有未结算仓位占用该信号。
     *
     * @return list<SignalModel>
     */
    public function getAvailablePool(): array
    {
        $now = time();
        $busyIds = $this->getBusySignalIds();

        $rows = $this->fetchAll([
            'status'     => SignalModel::STATUS_VALID,
            'started_at' => ['between', [$now - 7200, $now + 86400]],
            'size'       => 300,
        ], ['profit_rate' => 'desc']);

        $out = [];
        $fixtureSvc = new FixtureService();
        foreach ($rows as $row) {
            $sid = (int) $row->id;
            if (isset($busyIds[$sid])) {
                continue;
            }
            if ((int) $row->last_seen_at > 0 && (int) $row->last_seen_at < $now - 3600) {
                continue;
            }
            $rate = (float) $row->profit_rate;
            $minRate = (float) (config('arbitrage.engine.signal_min_rate') ?? 0.004);
            $maxRate = (float) (config('arbitrage.engine.signal_max_rate') ?? 0.10);
            if ($rate < $minRate || $rate > $maxRate + 1e-12) {
                continue;
            }
            $fixtureId = (int) $row->fixture_id;
            if ($fixtureId > 0) {
                $fx = $fixtureSvc->get($fixtureId);
                if ($fx && ((int) $fx->is_finished === FixtureModel::FINISHED || (int) $fx->status !== FixtureModel::STATUS_NORMAL)) {
                    continue;
                }
            }
            $out[] = $row;
        }
        return $out;
    }

    /**
     * 仍锁仓/待结算的仓位所占用的 signal_id（未结算不可再买同一信号）。
     *
     * @return array<int,true>
     */
    public function getBusySignalIds(): array
    {
        $rows = \support\Db::table('arbitrage_position')
            ->whereIn('phase', [
                PositionModel::PHASE_LOCKED,
                PositionModel::PHASE_PENDING_SETTLE,
            ])
            ->where('signal_id', '>', 0)
            ->distinct()
            ->pluck('signal_id');

        $map = [];
        foreach ($rows as $id) {
            $map[(int) $id] = true;
        }
        return $map;
    }

    /**
     * 原子占用信号：仅当仍为 VALID 时改为 USED。
     * @return bool 是否占用成功（失败说明已被别人买走或非有效）
     */
    public function tryClaimSignal(int $signalId): bool
    {
        if ($signalId <= 0) {
            return false;
        }
        $n = \support\Db::table('arbitrage_signal')
            ->where('id', $signalId)
            ->where('status', SignalModel::STATUS_VALID)
            ->update([
                'status'       => SignalModel::STATUS_USED,
                'updated_time' => time(),
            ]);
        return $n > 0;
    }

    /**
     * 从池中选一条信号。
     * 过滤 [signal_min_rate, signal_max_rate]（默认丢弃 >10%）。
     * 优先高收益：按 r 降序，只在顶部切片随机，利于高日目标最少笔达标。
     *
     * @param list<SignalModel> $pool
     * @param array{
     *   prefer_high?:bool,
     *   target_rate?:float,
     *   shortfall_profit?:float,
     *   target_amount?:float
     * } $opts
     */
    public function selectSignal(array $pool, int $remainingWindows = 1, array $opts = []): ?SignalModel
    {
        if ($pool === []) {
            return null;
        }
        $minRate = (float) (config('arbitrage.engine.signal_min_rate') ?? 0.004);
        $maxRate = (float) (config('arbitrage.engine.signal_max_rate') ?? 0.10);
        $preferHigh = array_key_exists('prefer_high', $opts)
            ? (bool) $opts['prefer_high']
            : (bool) (config('arbitrage.engine.signal_prefer_high_rate') ?? true);

        $filtered = array_values(array_filter(
            $pool,
            static fn(SignalModel $s): bool => (int) $s->status === SignalModel::STATUS_VALID
                && (float) $s->profit_rate + 1e-12 >= $minRate
                && (float) $s->profit_rate <= $maxRate + 1e-12
        ));
        if ($filtered === []) {
            return null;
        }

        usort(
            $filtered,
            static fn(SignalModel $a, SignalModel $b): int => (float) $b->profit_rate <=> (float) $a->profit_rate
        );

        if (!$preferHigh) {
            return $filtered[array_rand($filtered)];
        }

        $n = count($filtered);
        if ($n <= 3) {
            return $filtered[0];
        }

        $targetRate = (float) ($opts['target_rate'] ?? 0);
        $shortfall = (float) ($opts['shortfall_profit'] ?? 0);
        $amount = max(0.0, (float) ($opts['target_amount'] ?? 0));
        $shortfallRate = $amount > 0 ? $shortfall / $amount : 0.0;

        $topRatio = match (true) {
            $shortfallRate >= 0.04 || $targetRate >= 0.05 => 0.15,
            $shortfallRate >= 0.02 || $targetRate >= 0.03 => 0.25,
            $remainingWindows <= 1 => 0.20,
            default => 0.40,
        };
        $topN = max(3, (int) ceil($n * $topRatio));
        $topN = min($topN, $n);
        return $filtered[mt_rand(0, $topN - 1)];
    }

    /** @deprecated 请用 tryClaimSignal；保留兼容 */
    public function markUsed(int $signalId): void
    {
        $this->tryClaimSignal($signalId);
    }

    /** 开仓失败时释放刚占用的信号，允许后续重试 */
    public function releaseClaim(int $signalId): void
    {
        if ($signalId <= 0) {
            return;
        }
        \support\Db::table('arbitrage_signal')
            ->where('id', $signalId)
            ->where('status', SignalModel::STATUS_USED)
            ->update([
                'status'       => SignalModel::STATUS_VALID,
                'updated_time' => time(),
            ]);
    }

    /** @return array<string,mixed> */
    public function toArray(SignalModel $m): array
    {
        return [
            'id'                => (int) $m->id,
            'event_id'          => (int) $m->event_id,
            'fixture_id'        => (int) $m->fixture_id,
            'arb_hash'          => (string) $m->arb_hash,
            'event_name'        => (string) $m->event_name,
            'is_live'           => (int) $m->is_live,
            'started_at'        => (int) $m->started_at,
            'betburger_pct'     => (float) $m->betburger_pct,
            'profit_rate'       => (float) $m->profit_rate,
            'leg1_bookmaker_id' => (int) $m->leg1_bookmaker_id,
            'leg1_bookmaker'    => (string) $m->leg1_bookmaker,
            'leg1_market'       => (string) $m->leg1_market,
            'leg1_odds'         => (float) $m->leg1_odds,
            'leg2_bookmaker_id' => (int) $m->leg2_bookmaker_id,
            'leg2_bookmaker'    => (string) $m->leg2_bookmaker,
            'leg2_market'       => (string) $m->leg2_market,
            'leg2_odds'         => (float) $m->leg2_odds,
            'preview_stake'     => (float) $m->preview_stake,
            'current_score'     => (string) $m->current_score,
        ];
    }

    /**
     * 下单前刷新赔率（找不到则返回 null）。
     * @return array<string,mixed>|null
     */
    public function resolveLiveSignal(ArbitrageEngine $engine, SignalModel $signal): ?array
    {
        try {
            $live = $engine->betBurger()->findSignal((int) $signal->event_id, (string) $signal->arb_hash);
            if ($live !== null) {
                return $live;
            }
        } catch (\Throwable $e) {
            Log::channel('library')->warning('刷新信号赔率失败，降级用库快照', ['msg' => $e->getMessage()]);
        }
        return $signal->toM();
    }

    /** @param array<string,mixed> $s */
    public static function isValidArbitrage(array $s): bool
    {
        $o1 = (float) ($s['leg1_odds'] ?? $s['leg1']['odds'] ?? 0);
        $o2 = (float) ($s['leg2_odds'] ?? $s['leg2']['odds'] ?? 0);
        if ($o1 <= 1.0 || $o2 <= 1.0) {
            return false;
        }
        return (1.0 / $o1 + 1.0 / $o2) < 0.995;
    }
}
