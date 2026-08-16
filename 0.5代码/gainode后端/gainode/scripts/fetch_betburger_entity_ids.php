<?php
/**
 * @deprecated 已迁移至 Console 命令，请使用：
 *   php webman arbitrage:entity-map [--dry] [--backfill] [--from-cache]
 *
 * 本文件仅保留兼容跳转，后续版本将删除。
 */
declare(strict_types=1);

$root = dirname(__DIR__);
passthru(PHP_BINARY . ' ' . escapeshellarg($root . '/webman') . ' arbitrage:entity-map '
    . implode(' ', array_map('escapeshellarg', array_slice($argv, 1))), $code);
exit($code);
