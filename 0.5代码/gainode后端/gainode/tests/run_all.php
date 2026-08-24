<?php

declare(strict_types=1);

/**
 * 统一后端测试入口（NEXT-01 步骤7 / P2-QUALITY-001 修复）。
 *
 * 背景：composer test 原先只执行 tests/ledger 单脚本，不能代表全部 26 个测试。
 * 本运行器递归发现 tests/ 下所有 *Test.php（排除 _bootstrap.php 等下划线前缀公共引导），
 * 每个测试以独立 PHP 子进程运行（与既有"独立 CLI 脚本，无需 PHPUnit"约定一致），
 * 按退出码判定 PASS/FAIL：任一失败 → 本脚本 exit 1。
 *
 * 用法：
 *   php tests/run_all.php              # 运行全部
 *   php tests/run_all.php Contract     # 仅运行路径含该子串的测试
 *
 * 不接触真实数据库/.env/网络：各套件沿用各自 _bootstrap 的 SQLite in-memory 或 Null 存储。
 */

$root = __DIR__;
$filter = $argv[1] ?? '';

$files = [];
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
);
foreach ($it as $f) {
    if (!$f->isFile()) {
        continue;
    }
    $name = $f->getFilename();
    if (!str_ends_with($name, 'Test.php')) {
        continue;
    }
    if (str_starts_with($name, '_')) {
        continue; // _bootstrap.php 等公共引导不单独执行
    }
    $path = str_replace('\\', '/', $f->getPathname());
    if ($filter !== '' && !str_contains($path, $filter)) {
        continue;
    }
    $files[] = $path;
}
sort($files);

if ($files === []) {
    echo "NO TESTS FOUND (filter={$filter})\n";
    exit(1);
}

$php = escapeshellarg(PHP_BINARY);
$rootNorm = str_replace('\\', '/', $root) . '/';
$passed = 0;
$failed = [];

foreach ($files as $file) {
    $rel = str_replace($rootNorm, '', str_replace('\\', '/', $file));
    $output = [];
    $code = 0;
    exec($php . ' ' . escapeshellarg($file) . ' 2>&1', $output, $code);
    if ($code === 0) {
        $passed++;
        echo "[PASS] {$rel}\n";
    } else {
        $failed[] = $rel;
        echo "[FAIL] {$rel} (exit={$code})\n";
        // 失败时输出末尾 30 行便于定位
        echo implode("\n", array_slice($output, -30)) . "\n";
    }
}

echo "=====================================================\n";
echo sprintf("TOTAL: %d | SUITE PASS: %d | SUITE FAIL: %d\n", count($files), $passed, count($failed));
if ($failed !== []) {
    foreach ($failed as $f) {
        echo "  FAILED: {$f}\n";
    }
    echo "TEST FAILED\n";
    exit(1);
}
echo "ALL SUITES PASS\n";
exit(0);
