<?php

declare(strict_types=1);

/**
 * S02-P01 Integration 测试公共引导（独立 CLI 脚本，无需 PHPUnit）。
 * Null 内核存储不触数据库，仅加载 autoload。
 */

require __DIR__ . '/../../vendor/autoload.php';

$GLOBALS['__int_pass'] = 0;
$GLOBALS['__int_fail'] = 0;

function check(bool $cond, string $label): void
{
    if ($cond) {
        $GLOBALS['__int_pass']++;
        echo "PASS: {$label}\n";
    } else {
        $GLOBALS['__int_fail']++;
        echo "FAIL: {$label}\n";
    }
}

function summary(): void
{
    $pass = $GLOBALS['__int_pass'];
    $fail = $GLOBALS['__int_fail'];
    echo "=====================================================\n";
    echo "RESULT: pass={$pass} fail={$fail}\n";
    echo "=====================================================\n";
    if ($fail > 0) {
        echo "TEST FAILED\n";
        exit(1);
    }
    echo "ALL PASS\n";
    exit(0);
}
