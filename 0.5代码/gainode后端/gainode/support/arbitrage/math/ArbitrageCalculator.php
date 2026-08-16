<?php
declare(strict_types=1);

namespace support\arbitrage\math;

/**
 * 套利数学：按两腿赔率拆分注额并计算理论利润率（小数口径）。
 *
 * @return array{
 *   odds1:float,odds2:float,implied_p1:float,implied_p2:float,sum_p:float,
 *   profit_rate:float,total_stake:float,stake1:float,stake2:float,payout:float,profit:float
 * }
 */
final class ArbitrageCalculator
{
    public function calculate(float $odds1, float $odds2, float $totalStake): array
    {
        if ($odds1 <= 1.0 || $odds2 <= 1.0) {
            throw new \InvalidArgumentException('odds must be > 1.0');
        }
        if ($totalStake <= 0.0) {
            throw new \InvalidArgumentException('totalStake must be > 0');
        }

        $p1 = 1.0 / $odds1;
        $p2 = 1.0 / $odds2;
        $sumP = $p1 + $p2;
        if ($sumP >= 1.0) {
            throw new \DomainException(sprintf('no arbitrage: implied prob sum = %.6f >= 1', $sumP));
        }

        $rate = 1.0 / $sumP - 1.0;
        $stake1 = $totalStake * $p1 / $sumP;
        $stake2 = $totalStake * $p2 / $sumP;
        $payout = $totalStake / $sumP;

        return [
            'odds1'        => $odds1,
            'odds2'        => $odds2,
            'implied_p1'   => $p1,
            'implied_p2'   => $p2,
            'sum_p'        => $sumP,
            'profit_rate'  => $rate,
            'total_stake'  => Money::round2($totalStake),
            'stake1'       => Money::round2($stake1),
            'stake2'       => Money::round2($stake2),
            'payout'       => Money::round2($payout),
            'profit'       => Money::round2($payout - $totalStake),
        ];
    }
}
