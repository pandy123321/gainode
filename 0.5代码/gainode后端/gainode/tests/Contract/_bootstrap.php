<?php

declare(strict_types=1);

/**
 * S02-P01 Contract 测试公共引导（独立 CLI 脚本，无需 PHPUnit）。
 * 仅加载 autoload，不建立数据库连接（envelope/错误分类为纯逻辑）。
 */

require __DIR__ . '/../../vendor/autoload.php';

$GLOBALS['__contract_pass'] = 0;
$GLOBALS['__contract_fail'] = 0;

function check(bool $cond, string $label): void
{
    if ($cond) {
        $GLOBALS['__contract_pass']++;
        echo "PASS: {$label}\n";
    } else {
        $GLOBALS['__contract_fail']++;
        echo "FAIL: {$label}\n";
    }
}

function summary(): void
{
    $pass = $GLOBALS['__contract_pass'];
    $fail = $GLOBALS['__contract_fail'];
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
