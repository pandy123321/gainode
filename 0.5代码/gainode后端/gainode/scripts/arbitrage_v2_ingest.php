<?php
/**
 * @deprecated 已迁移至 Console 命令，请使用：
 *   php webman arbitrage:ingest [--fixtures-only] [--signals-only] [--dry-run]
 *
 * 本文件仅保留兼容跳转，后续版本将删除。
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$args = array_slice($argv, 1);
passthru(PHP_BINARY . ' ' . escapeshellarg($root . '/webman') . ' arbitrage:ingest '
    . implode(' ', array_map('escapeshellarg', $args)), $code);
exit($code);
