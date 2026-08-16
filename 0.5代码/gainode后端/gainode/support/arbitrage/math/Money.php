<?php
declare(strict_types=1);

namespace support\arbitrage\math;

/** 金额四舍五入（保留 2 位小数）。 */
final class Money
{
    public static function round2(float $value): float
    {
        return round($value * 100.0, 0, PHP_ROUND_HALF_UP) / 100.0;
    }

    public static function goalMet(float $realizedProfit, float $targetProfit): bool
    {
        return self::round2($realizedProfit) >= self::round2($targetProfit) && $targetProfit > 0;
    }
}
