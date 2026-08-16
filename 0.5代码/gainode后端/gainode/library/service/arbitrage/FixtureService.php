<?php

namespace library\service\arbitrage;

use library\dao\arbitrage\FixtureDao;
use library\model\arbitrage\FixtureModel;
use library\model\arbitrage\PositionModel;
use library\model\arbitrage\SignalModel;
use support\arbitrage\ArbitrageEngine;
use support\arbitrage\match\FixtureMatcher;
use support\extend\Log;
use support\extend\Service;

/**
 * 比赛主数据：同步 / 占位 / 升级 / 完赛标记
 *
 * @method FixtureModel create($data)
 * @method FixtureModel updateOrCreate(array $params, array $data)
 * @method FixtureModel update($id, array $data)
 * @method FixtureModel get($id, string $field = null)
 * @method FixtureModel find($id)
 * @method FixtureModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 */
class FixtureService extends Service
{
    public function __construct()
    {
        $this->dao = FixtureDao::class;
        parent::__construct();
    }

    /**
     * 从 API-Football 同步今天+昨天(+滚球)比赛并尝试升级占位。
     */
    public function syncFromApiFootball(ArbitrageEngine $engine): int
    {
        $tz = $engine->timezone();
        $client = $engine->apiFootball();
        $rows = $client->fetchBusinessWindow($tz);
        try {
            $rows = array_merge($rows, $client->fetchLive());
        } catch (\Throwable $e) {
            Log::channel('library')->warning('套利滚球同步跳过: ' . $e->getMessage());
        }

        $uniq = [];
        foreach ($rows as $row) {
            $sid = (int) ($row['source_id'] ?? 0);
            if ($sid > 0) {
                $uniq[$sid] = $row;
            }
        }

        $n = 0;
        foreach ($uniq as $row) {
            $this->upsertFromApi($row);
            $n++;
        }
        $upgraded = $this->upgradePlaceholders($engine->fixtureMatcher());
        if ($upgraded > 0) {
            Log::channel('library')->info('占位比赛升级', ['count' => $upgraded]);
        }
        return $n;
    }

    /**
     * 按状态短码刷新 is_finished（用于结算闭环）。
     */
    public function settleFinished(): int
    {
        $now = time();
        $n = 0;
        $rows = $this->fetchAll([
            'status'       => FixtureModel::STATUS_NORMAL,
            'is_finished'  => FixtureModel::NOT_FINISHED,
            'size'         => 2000,
        ]);
        foreach ($rows as $fx) {
            $short = strtoupper((string) $fx->status_short);
            if (in_array($short, FixtureModel::FINISHED_SHORT, true)) {
                $this->update((int) $fx->id, [
                    'is_finished'  => FixtureModel::FINISHED,
                    'updated_time' => $now,
                ]);
                $n++;
            }
        }
        // 占位赛长期未升级会导致仓位无法结算：超时后标记为作废态，交给仓位 void 释放资金
        $n += $this->markStalePlaceholdersVoid($now);
        return $n;
    }

    /**
     * source=2 占位赛兜底：开赛后长时间仍未升级为真实赛，则标记为作废状态。
     * 这样 PositionService::voidCancelledPositions() 能自动作废对应仓位并释放占用资金。
     */
    private function markStalePlaceholdersVoid(int $now): int
    {
        $ttl = (int) (config('arbitrage.engine.placeholder_void_after_seconds') ?? 6 * 3600);
        if ($ttl < 1800) {
            $ttl = 1800;
        }

        $rows = $this->fetchAll([
            'status'         => FixtureModel::STATUS_NORMAL,
            'source'         => FixtureModel::SOURCE_PLACEHOLDER,
            'is_placeholder' => FixtureModel::IS_PLACEHOLDER,
            'is_finished'    => FixtureModel::NOT_FINISHED,
            'size'           => 2000,
        ], ['id' => 'asc']);

        $n = 0;
        foreach ($rows as $fx) {
            $kickoff = (int) $fx->kickoff_at;
            if ($kickoff <= 0) {
                continue;
            }
            if ($now < $kickoff + $ttl) {
                continue;
            }
            $this->update((int) $fx->id, [
                'status_short' => 'PST',
                'status_long'  => 'Placeholder timeout (auto-void)',
                'is_finished'  => FixtureModel::FINISHED,
                'updated_time' => $now,
            ]);
            $n++;
        }
        return $n;
    }

    /**
     * @param array<string,mixed> $row ApiFootballClient 归一化结构
     */
    public function upsertFromApi(array $row): int
    {
        $sourceId = (int) ($row['source_id'] ?? 0);
        if ($sourceId <= 0) {
            return 0;
        }
        $now = time();
        $short = (string) ($row['status_short'] ?? '');
        $data = [
            'source'         => FixtureModel::SOURCE_API,
            'source_id'      => $sourceId,
            'is_placeholder' => FixtureModel::NOT_PLACEHOLDER,
            'league'         => (string) ($row['league'] ?? ''),
            'home'           => (string) ($row['home'] ?? ''),
            'away'           => (string) ($row['away'] ?? ''),
            'timezone'       => (string) ($row['timezone'] ?? ''),
            'kickoff_at'     => (int) ($row['kickoff_at'] ?? 0),
            'status_short'   => $short,
            'status_long'    => (string) ($row['status_long'] ?? ''),
            'score_home'     => (int) ($row['score_home'] ?? 0),
            'score_away'     => (int) ($row['score_away'] ?? 0),
            'is_finished'    => in_array($short, FixtureModel::FINISHED_SHORT, true)
                ? FixtureModel::FINISHED
                : (int) ($row['is_finished'] ?? 0),
            'updated_time'   => $now,
            'status'         => FixtureModel::STATUS_NORMAL,
        ];

        $existing = $this->fetch([
            'source'    => FixtureModel::SOURCE_API,
            'source_id' => $sourceId,
        ]);
        if ($existing) {
            $this->update((int) $existing->id, $data);
            return (int) $existing->id;
        }
        $data['created_time'] = $now;
        $created = $this->create($data);
        return (int) ($created->id ?? 0);
    }

    /**
     * 为信号确保 fixture：映射命中 → 模糊匹配 → 占位。
     *
     * @param array<string,mixed> $signal
     * @param list<array<string,mixed>>|null $pool
     */
    public function ensureFixtureForSignal(array $signal, ?array $pool = null, ?FixtureMatcher $matcher = null): int
    {
        $eventId = (int) ($signal['event_id'] ?? 0);
        if ($eventId <= 0) {
            return 0;
        }

        $byEvent = $this->fetch(['betburger_event_id' => $eventId, 'status' => FixtureModel::STATUS_NORMAL]);
        if ($byEvent) {
            return (int) $byEvent->id;
        }

        $pool ??= $this->loadMatchPool();
        $matcher ??= new FixtureMatcher();
        $hit = $matcher->match($signal, $pool);
        if ($hit !== null) {
            $id = (int) ($hit['id'] ?? 0);
            if ($id > 0) {
                $this->bindBetburgerEvent($id, $eventId);
                return $id;
            }
        }

        return $this->ensurePlaceholder($signal);
    }

    /**
     * @param array<string,mixed> $signal
     */
    public function ensurePlaceholder(array $signal): int
    {
        $eventId = (int) ($signal['event_id'] ?? 0);
        if ($eventId <= 0) {
            return 0;
        }
        $now = time();

        $existing = $this->fetch(['betburger_event_id' => $eventId]);
        if ($existing) {
            return (int) $existing->id;
        }
        $existing = $this->fetch([
            'source'    => FixtureModel::SOURCE_PLACEHOLDER,
            'source_id' => $eventId,
        ]);
        if ($existing) {
            $this->update((int) $existing->id, [
                'league'       => (string) ($signal['league'] ?? $existing->league),
                'home'         => (string) ($signal['home'] ?? $existing->home),
                'away'         => (string) ($signal['away'] ?? $existing->away),
                'kickoff_at'   => (int) ($signal['started_at'] ?? $existing->kickoff_at),
                'updated_time' => $now,
            ]);
            return (int) $existing->id;
        }

        $created = $this->create([
            'source'             => FixtureModel::SOURCE_PLACEHOLDER,
            'source_id'          => $eventId,
            'betburger_event_id' => $eventId,
            'is_placeholder'     => FixtureModel::IS_PLACEHOLDER,
            'league'             => (string) ($signal['league'] ?? ''),
            'home'               => (string) ($signal['home'] ?? ''),
            'away'               => (string) ($signal['away'] ?? ''),
            'timezone'           => '',
            'kickoff_at'         => (int) ($signal['started_at'] ?? 0),
            'status_short'       => 'NS',
            'status_long'        => 'Placeholder',
            'score_home'         => 0,
            'score_away'         => 0,
            'is_finished'        => FixtureModel::NOT_FINISHED,
            'created_time'       => $now,
            'updated_time'       => $now,
            'status'             => FixtureModel::STATUS_NORMAL,
        ]);
        return (int) ($created->id ?? 0);
    }

    public function bindBetburgerEvent(int $fixtureId, int $eventId): void
    {
        if ($fixtureId <= 0 || $eventId <= 0) {
            return;
        }
        $taken = $this->fetch([
            'betburger_event_id' => $eventId,
            'id'                 => ['neq', $fixtureId],
        ]);
        if ($taken) {
            return;
        }
        $this->update($fixtureId, [
            'betburger_event_id' => $eventId,
            'updated_time'       => time(),
        ]);
    }

    /**
     * 占位升级为真实比赛，并改写 signal/position.fixture_id。
     */
    public function upgradePlaceholders(?FixtureMatcher $matcher = null): int
    {
        $matcher ??= new FixtureMatcher();
        $pool = $this->loadMatchPool();
        if ($pool === []) {
            return 0;
        }

        $placeholders = $this->fetchAll([
            'is_placeholder' => FixtureModel::IS_PLACEHOLDER,
            'status'         => FixtureModel::STATUS_NORMAL,
            'size'           => 500,
        ]);
        $n = 0;
        $now = time();
        foreach ($placeholders as $ph) {
            $hit = $matcher->match([
                'home'   => (string) $ph->home,
                'away'   => (string) $ph->away,
                'league' => (string) $ph->league,
            ], $pool);
            if ($hit === null) {
                continue;
            }
            $realId = (int) ($hit['id'] ?? 0);
            if ($realId <= 0 || $realId === (int) $ph->id) {
                continue;
            }

            $eventId = (int) ($ph->betburger_event_id ?: $ph->source_id);
            if ($eventId > 0) {
                $this->bindBetburgerEvent($realId, $eventId);
            }

            (new SignalModel())->newQuery()
                ->where('fixture_id', (int) $ph->id)
                ->update(['fixture_id' => $realId, 'updated_time' => $now]);
            (new PositionModel())->newQuery()
                ->where('fixture_id', (int) $ph->id)
                ->update(['fixture_id' => $realId, 'updated_time' => $now]);

            $this->update((int) $ph->id, [
                'status'       => FixtureModel::STATUS_DELETED,
                'updated_time' => $now,
            ]);
            $n++;
        }
        return $n;
    }

    /**
     * @return list<array{id:int,source_id:int,home:string,away:string,league:string}>
     */
    public function loadMatchPool(): array
    {
        $rows = $this->fetchAll([
            'status'         => FixtureModel::STATUS_NORMAL,
            'is_placeholder' => FixtureModel::NOT_PLACEHOLDER,
            'is_finished'    => FixtureModel::NOT_FINISHED,
            'size'           => 2000,
        ], ['kickoff_at' => 'desc'], ['id', 'source_id', 'home', 'away', 'league']);

        $pool = [];
        foreach ($rows as $r) {
            $pool[] = [
                'id'        => (int) $r->id,
                'source_id' => (int) $r->source_id,
                'home'      => (string) $r->home,
                'away'      => (string) $r->away,
                'league'    => (string) $r->league,
            ];
        }
        return $pool;
    }

    public function isVoidStatus(string $statusShort): bool
    {
        return in_array(strtoupper($statusShort), FixtureModel::VOID_SHORT, true);
    }

    public function isSettledReady(FixtureModel $fx): bool
    {
        return (int) $fx->is_placeholder === FixtureModel::NOT_PLACEHOLDER
            && (int) $fx->is_finished === FixtureModel::FINISHED
            && (int) $fx->status === FixtureModel::STATUS_NORMAL;
    }
}
