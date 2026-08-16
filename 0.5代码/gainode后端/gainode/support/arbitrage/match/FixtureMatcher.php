<?php
declare(strict_types=1);

namespace support\arbitrage\match;

/**
 * 把 BetBurger 赛事与 API-Football 比赛按名称模糊对齐。
 * 入参均为数组，不依赖 DTO。
 */
final class FixtureMatcher
{
    public const MIN_MATCH_SCORE = 0.72;

    /**
     * @param array{home?:string,away?:string,league?:string} $signal
     * @param list<array{id?:int,home?:string,away?:string,league?:string}> $fixtures
     * @return array<string,mixed>|null 命中时带回原 fixture + match_score
     */
    public function match(array $signal, array $fixtures): ?array
    {
        $league = self::normalize((string) ($signal['league'] ?? ''));
        $home = (string) ($signal['home'] ?? '');
        $away = (string) ($signal['away'] ?? '');
        $best = null;
        $bestScore = 0.0;

        foreach ($fixtures as $fixture) {
            if (!is_array($fixture)) {
                continue;
            }
            $fxLeague = self::normalize((string) ($fixture['league'] ?? ''));
            $leagueScore = ($league !== '' && $fxLeague !== '')
                ? ($league === $fxLeague ? 1.0 : self::tokenDice($league, $fxLeague))
                : 0.0;
            if ($leagueScore < 0.25) {
                continue;
            }
            $fxHome = (string) ($fixture['home'] ?? '');
            $fxAway = (string) ($fixture['away'] ?? '');
            $direct = min(self::similarity($home, $fxHome), self::similarity($away, $fxAway));
            $reversed = min(self::similarity($home, $fxAway), self::similarity($away, $fxHome));
            $score = max($direct, $reversed);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $fixture + ['match_score' => $score, 'league_score' => $leagueScore];
            }
        }

        return $bestScore >= self::MIN_MATCH_SCORE ? $best : null;
    }

    public static function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = str_replace(['.', '-', '_'], ['', ' ', ' '], $value);
        return preg_replace('/\s+/u', ' ', $value) ?? '';
    }

    public static function similarity(string $left, string $right): float
    {
        $l = self::normalize($left);
        $r = self::normalize($right);
        if ($l === '' || $r === '') {
            return 0.0;
        }
        if ($l === $r) {
            return 1.0;
        }
        similar_text($l, $r, $pct);
        $dice = self::tokenDice($l, $r);
        return max($pct / 100.0, $dice);
    }

    public static function tokenDice(string $left, string $right): float
    {
        $a = preg_split('/\s+/u', self::normalize($left), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $b = preg_split('/\s+/u', self::normalize($right), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($a === [] || $b === []) {
            return 0.0;
        }
        $counts = array_count_values($a);
        $hit = 0;
        foreach ($b as $token) {
            if (($counts[$token] ?? 0) > 0) {
                $hit++;
                $counts[$token]--;
            }
        }
        return min(1.0, 2.0 * $hit / (count($a) + count($b)));
    }
}
