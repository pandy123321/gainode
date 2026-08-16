<?php
/**
 * @deprecated 已迁移至 Console 命令，请使用：
 *   php webman arbitrage:flow-test [--project=1] [--amount=10000] [--force-window] [--rounds=3] [--sync]
 *
 * 本文件仅保留兼容跳转，后续版本将删除。
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$mapped = [];
foreach (array_slice($argv, 1) as $arg) {
    // getopt 风格 --project=1 可直接透传给 Symfony
    $mapped[] = $arg;
}
passthru(PHP_BINARY . ' ' . escapeshellarg($root . '/webman') . ' arbitrage:flow-test '
    . implode(' ', array_map('escapeshellarg', $mapped)), $code);
exit($code);
