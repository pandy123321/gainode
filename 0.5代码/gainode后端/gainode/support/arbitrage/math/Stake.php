<?php
declare(strict_types=1);

namespace support\arbitrage\math;

/**
 * 注额与日窗口调度。
 *
 * 业务原则：
 * - 日计划目标：在 target_amount 预算内达成 target_profit（对应日利率），且不超
 * - 最少笔数：一笔尽量吃满「剩余目标利润」；注额 = 剩余利润 / 信号利率，且不超过可用本金
 * - 尾单：可用本金充足、利润缺口变小时自动降额贴利率
 * - target_trades / schedule 仅为时间窗，不是均分拆单
 */
final class Stake
{
    /**
     * 按「最少笔冲目标」计算本笔总注额。
     *
     * @param array{
     *   target_profit?:float,
     *   realized_profit?:float,
     *   target_amount?:float,
     *   already_staked?:float,
     *   target_trades?:int,
     *   done_trades?:int,
     *   schedule?:list<int>,
     *   next_idx?:int
     * } $plan
     */
    public static function calculate(array $plan, float $profitRate, ?float $jitter = null): float
    {
        if ($profitRate <= 0.0) {
            return 0.0;
        }

        $remainingProfit = Money::round2(
            (float) ($plan['target_profit'] ?? 0) - (float) ($plan['realized_profit'] ?? 0)
        );
        if ($remainingProfit <= 0.0) {
            return 0.0;
        }

        $targetAmount = max(0.0, (float) ($plan['target_amount'] ?? 0));
        $alreadyStaked = max(0.0, (float) ($plan['already_staked'] ?? 0));
        $remainingCapital = Money::round2($targetAmount - $alreadyStaked);
        if ($remainingCapital < 1.0) {
            return 0.0;
        }

        // 略低估利率算注额，降低滑点后超目标风险
        $buffer = (float) (config('arbitrage.engine.stake_rate_buffer') ?? 0.95);
        $buffer = max(0.85, min(1.0, $buffer));
        $effRate = $profitRate * $buffer;
        if ($effRate <= 0.0) {
            return 0.0;
        }

        $stakeIdeal = $remainingProfit / $effRate;
        $stakeNoOvershoot = $remainingProfit / $profitRate;
        $stake = min($stakeIdeal, $stakeNoOvershoot, $remainingCapital);

        // 仅向下 0～2% 抖动，不拆大单
        $jitter ??= mt_rand(0, 10000) / 10000.0;
        $stake *= (1.0 - 0.02 * $jitter);

        if ($stake >= 10.0) {
            $stake = floor($stake / 10.0) * 10.0;
        } else {
            if ($stake < 1.0) {
                return 0.0;
            }
            $stake = Money::round2(min($stake, $remainingCapital, $stakeNoOvershoot));
            return $stake >= 1.0 ? $stake : 0.0;
        }

        if ($stake < 10.0) {
            $hardCap = min($remainingCapital, $stakeNoOvershoot);
            if ($hardCap >= 10.0) {
                $stake = 10.0;
            } elseif ($hardCap >= 1.0) {
                return Money::round2($hardCap);
            } else {
                return 0.0;
            }
        }

        $stake = min($stake, $remainingCapital, $stakeNoOvershoot);
        if ($stake >= 10.0) {
            $stake = floor($stake / 10.0) * 10.0;
        }

        return $stake >= 10.0 ? Money::round2($stake) : 0.0;
    }

    /**
     * 剩余时间窗数（仅调度用）。
     *
     * @param array<string,mixed> $plan
     */
    public static function remainingAttempts(array $plan): int
    {
        $schedule = is_array($plan['schedule'] ?? null) ? $plan['schedule'] : [];
        $nextIdx = (int) ($plan['next_idx'] ?? 0);
        $bySchedule = count($schedule) - $nextIdx;
        if ($bySchedule > 0) {
            return $bySchedule;
        }

        $targetTrades = (int) ($plan['target_trades'] ?? 0);
        $doneTrades = (int) ($plan['done_trades'] ?? 0);
        if ($targetTrades > 0) {
            return max(1, $targetTrades - $doneTrades);
        }
        return 1;
    }

    /**
     * @return list<int>
     */
    public static function generateSchedule(int $count, string $timezone = 'America/New_York'): array
    {
        if ($count <= 0) {
            return [];
        }
        $tz = new \DateTimeZone($timezone);
        $base = (new \DateTimeImmutable('now', $tz))->setTime(0, 0, 0);
        $cutoff = 24 * 60 - 65;
        $mins = [];
        for ($i = 0; $i < $count; $i++) {
            $mins[] = min(mt_rand(0, 100) < 70 ? 540 + mt_rand(0, 899) : 480 + mt_rand(0, 1079), $cutoff);
        }
        sort($mins, SORT_NUMERIC);
        for ($i = 1; $i < count($mins); $i++) {
            if ($mins[$i] - $mins[$i - 1] < 30) {
                $mins[$i] = min($mins[$i - 1] + 30 + mt_rand(0, 19), $cutoff);
            }
        }
        return array_map(
            static fn(int $m): int => $base->modify("+{$m} minutes")->getTimestamp(),
            $mins
        );
    }
}
