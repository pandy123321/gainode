<?php
declare(strict_types=1);

namespace support\arbitrage\http;

/** 博彩公司 / 玩法 ID → 展示名称映射。 */
final class ArrayNameMap
{
    /** @param array<int|string,string> $bookmakers @param array<int|string,string> $markets */
    public function __construct(private array $bookmakers = [], private array $markets = []) {}

    public function bookmaker(int $id): string
    {
        return (string) ($this->bookmakers[$id] ?? $this->bookmakers[(string) $id] ?? "Bookmaker#{$id}");
    }

    public function market(int $id, float $param): string
    {
        $base = $this->markets[$id] ?? $this->markets[(string) $id] ?? null;

        // 未配置映射：生成清晰占位符，参数用括号分隔避免与 ID 黏连（Market#19(5.25)）
        if ($base === null) {
            $suffix = $param !== 0.0 ? '(' . number_format($param, 2, '.', '') . ')' : '';
            return "Market#{$id}{$suffix}";
        }

        $base = (string) $base;

        // 仅当模板含 %s 才需要参数：有参数则替换，无参数则去掉占位符
        if (str_contains($base, '%s')) {
            if ($param === 0.0) {
                return str_replace('%s', '', $base);
            }
            return sprintf($base, number_format($param, 2, '.', ''));
        }

        // 不含 %s 的玩法（如 1X2 / 胜负）绝不拼接参数，避免 Match Winner5.25 这类错误
        return $base;
    }
}
