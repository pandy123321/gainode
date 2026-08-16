<?php

namespace library\service\arbitrage;

use DateTimeImmutable;
use DateTimeZone;
use library\dao\arbitrage\SignalRawDao;
use library\model\arbitrage\SignalRawModel;
use support\extend\Service;

/**
 * @method SignalRawModel create($data)
 * @method SignalRawModel updateOrCreate(array $params, array $data)
 * @method SignalRawModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 */
class SignalRawService extends Service
{
    public function __construct()
    {
        $this->dao = SignalRawDao::class;
        parent::__construct();
    }

    /** @param mixed $payload */
    public function upsertPayload(int $signalId, $payload): void
    {
        if ($signalId <= 0) {
            return;
        }
        $now = time();
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            $json = '{}';
        }
        $existing = $this->fetch(['signal_id' => $signalId]);
        if ($existing) {
            $this->update((int) $existing->signal_id, [
                'payload'      => $json,
                'updated_time' => $now,
            ]);
            return;
        }
        $this->create([
            'signal_id'    => $signalId,
            'payload'      => $json,
            'created_time' => $now,
            'updated_time' => $now,
        ]);
    }
}
