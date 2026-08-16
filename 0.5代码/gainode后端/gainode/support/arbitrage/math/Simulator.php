<?php
declare(strict_types=1);

namespace support\arbitrage\math;

/**
 * 简化撮合模拟：成功/滑点/失败，返回数组供 position/attempt 落库。
 */
final class Simulator
{
    /**
     * @param array{target_profit?:float,realized_profit?:float} $plan
     * @param array{leg1_odds?:float,leg2_odds?:float,profit_rate?:float,leg1?:array,leg2?:array} $signal
     * @return array{
     *   exec_status:string,success:bool,
     *   leg1_odds:float,leg2_odds:float,leg1_stake:float,leg2_stake:float,
     *   actual_rate:float,actual_profit:float,detail:array
     * }
     */
    public static function execute(array $plan, array $signal, float $stake): array
    {
        $o1 = (float) ($signal['leg1_odds'] ?? $signal['leg1']['odds'] ?? 0);
        $o2 = (float) ($signal['leg2_odds'] ?? $signal['leg2']['odds'] ?? 0);
        $rate = (float) ($signal['profit_rate'] ?? 0);
        if ($o1 <= 1.0 || $o2 <= 1.0 || $stake <= 0.0) {
            return self::fail('odds_reversed', $o1, $o2, $stake, ['reason' => 'invalid_odds']);
        }

        $p1 = 1.0 / $o1;
        $p2 = 1.0 / $o2;
        $sumP = $p1 + $p2;
        $s1 = Money::round2($stake * $p1 / $sumP);
        $s2 = Money::round2($stake * $p2 / $sumP);
        $roll = mt_rand(0, 10000) / 10000.0;
        $detail = ['roll' => $roll];

        // ~8% 失败，~20% 轻滑点成功，其余全成功
        if ($roll < 0.04) {
            return self::fail('market_closed', $o1, $o2, $stake, $detail);
        }
        if ($roll < 0.08) {
            return self::fail('limited', $o1, $o2, $stake, $detail);
        }

        $actualRate = $rate;
        $status = 'success';
        if ($roll < 0.28) {
            $slip = 0.002 + (mt_rand(0, 10000) / 10000.0) * 0.01;
            $newO2 = $o2 * (1.0 - $slip);
            $newP2 = 1.0 / $newO2;
            $newSum = $p1 + $newP2;
            if ($newSum >= 1.0) {
                return self::fail('odds_reversed', $o1, $o2, $stake, $detail + ['slip' => $slip]);
            }
            $actualRate = 1.0 / $newSum - 1.0;
            $s1 = Money::round2($stake * $p1 / $newSum);
            $s2 = Money::round2($stake * $newP2 / $newSum);
            $o2 = $newO2;
            $status = 'slippage';
            $detail['slip'] = $slip;
        }

        $profit = Money::round2($stake * $actualRate);
        $remaining = Money::round2((float) ($plan['target_profit'] ?? 0) - (float) ($plan['realized_profit'] ?? 0));
        if ($remaining <= 0.0) {
            return self::fail('goal_met', $o1, $o2, $stake, $detail);
        }
        if ($profit > $remaining) {
            $profit = $remaining;
            $actualRate = $stake > 0 ? $profit / $stake : 0.0;
            $detail['profit_truncated'] = true;
        }

        return [
            'exec_status'   => $status,
            'success'       => true,
            'leg1_odds'     => $o1,
            'leg2_odds'     => $o2,
            'leg1_stake'    => $s1,
            'leg2_stake'    => $s2,
            'actual_rate'   => round($actualRate, 4),
            'actual_profit' => $profit,
            'detail'        => $detail,
        ];
    }

    /** @return array{exec_status:string,success:bool,leg1_odds:float,leg2_odds:float,leg1_stake:float,leg2_stake:float,actual_rate:float,actual_profit:float,detail:array} */
    private static function fail(string $status, float $o1, float $o2, float $stake, array $detail): array
    {
        $p1 = $o1 > 1 ? 1.0 / $o1 : 0.5;
        $p2 = $o2 > 1 ? 1.0 / $o2 : 0.5;
        $sum = max(0.0001, $p1 + $p2);
        return [
            'exec_status'   => $status,
            'success'       => false,
            'leg1_odds'     => $o1,
            'leg2_odds'     => $o2,
            'leg1_stake'    => Money::round2($stake * $p1 / $sum),
            'leg2_stake'    => Money::round2($stake * $p2 / $sum),
            'actual_rate'   => 0.0,
            'actual_profit' => 0.0,
            'detail'        => $detail,
        ];
    }
}
