<?php
declare(strict_types=1);

namespace support\arbitrage;

use library\service\arbitrage\FixtureService;
use library\service\arbitrage\PositionService;
use library\service\arbitrage\SignalService;
use library\service\sys\DictService;
use support\arbitrage\client\ApiFootballClient;
use support\arbitrage\client\BetBurgerClient;
use support\arbitrage\math\Stake;
use support\arbitrage\match\FixtureMatcher;
use support\extend\Log;

/**
 * 套利引擎薄编排层（矿机项目 project_id 维度）。
 *
 * 日计划由业务侧 0 点任务创建；本引擎负责：行情同步 → 按计划窗口下单 → 结算。
 */
class ArbitrageEngine
{
    private array $conf;
    private BetBurgerClient $betBurger;
    private ApiFootballClient $apiFootball;

    public function __construct(?array $conf = null)
    {
        $default_configs = config('arbitrage') ?: [];
        if (empty($conf)) {
            $conf = $default_configs;
            $dictService = new DictService();
            $betburger = $dictService->getDictConfigs('betburger');
            $api_football = $dictService->getDictConfigs('api_football');
            if (!empty($betburger)) {
                $conf['betburger'] = array_merge($conf['betburger'] ?? [], $betburger);
            }
            if (!empty($api_football)) {
                $conf['api_football'] = array_merge($conf['api_football'] ?? [], $api_football);
            }
        }
        $this->conf = $conf;
        $this->betBurger = BetBurgerClient::fromConfig($this->conf);
        $this->apiFootball = ApiFootballClient::fromConfig($this->conf);
    }

    public function betBurger(): BetBurgerClient
    {
        return $this->betBurger;
    }

    public function apiFootball(): ApiFootballClient
    {
        return $this->apiFootball;
    }

    public function fixtureMatcher(): FixtureMatcher
    {
        return new FixtureMatcher();
    }

    public function timezone(): string
    {
        return (string) ($this->conf['business_timezone'] ?? 'America/New_York');
    }

    /** @return list<int> */
    public function generateSchedule(int $count): array
    {
        return Stake::generateSchedule($count, $this->timezone());
    }

    /**
     * 获取套利信号
     * @return int
     */
    public function ingestSignals(): int
    {
        try {
            return (new SignalService())->ingest($this);
        } catch (\Throwable $e) {
            Log::error('套利信号采集失败: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 获取比赛数据
     * @return int
     */
    public function syncFixtures(): int
    {
        try {
            return (new FixtureService())->syncFromApiFootball($this);
        } catch (\Throwable $e) {
            Log::error('比赛同步失败: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * 运行窗口下单
     * @return array
     */
    public function runOrderGeneration(): array
    {
        try {
            return (new PositionService())->runWindows($this);
        } catch (\Throwable $e) {
            Log::error('套利下单失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 结算
     * @return int
     */
    public function settle(): int
    {
        try {
            $n = (new FixtureService())->settleFinished();
            $n += (new PositionService())->settlePositions($this);
            return $n;
        } catch (\Throwable $e) {
            Log::error('套利结算失败: ' . $e->getMessage());
            return 0;
        }
    }
}
