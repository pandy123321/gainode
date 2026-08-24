<?php

declare(strict_types=1);

/**
 * OpenAPI 契约 lint（NEXT-01 步骤6 / BE-09、BE-10 修复）。
 *
 * 检查项：
 *   L1 主文档 paths ↔ paths/*.yaml 双向覆盖；
 *   L2 每个 operation：operationId 存在/唯一/格式、tags、responses 含 2xx、security 声明；
 *   L3 写操作（post）必须携带 Idempotency-Key 参数引用；
 *   L4 全部 $ref 可解析（目标文件存在 + 末段 key 在目标文件中存在）；
 *   L5 sys_route 种子 SQL ↔ OpenAPI 对账：种子键唯一、(METHOD,url) 必须在 OpenAPI 中存在；
 *
 * 用法：php openapi/lint.php   （全部通过 exit 0，任一失败 exit 1）
 * 说明：基于本仓库规整 YAML 风格的行级扫描（无锚点/无块标量），不引入外部依赖。
 */

$base = __DIR__;
$errors = [];
$warnings = [];

/** @return array<string,string> route => "file#/key" */
function parseMainPaths(string $mainFile): array
{
    $routes = [];
    $lines = file($mainFile, FILE_IGNORE_NEW_LINES) ?: [];
    $inPaths = false;
    $currentRoute = null;
    foreach ($lines as $line) {
        if (preg_match('/^(\S)/', $line, $m)) {
            $top = rtrim($line, ": \t");
            $inPaths = ($top === 'paths');
            $currentRoute = null;
            continue;
        }
        if (!$inPaths) {
            continue;
        }
        if (preg_match('/^\s{2}(\/[^\s:]+):\s*$/', $line, $m)) {
            $currentRoute = $m[1];
            continue;
        }
        if ($currentRoute !== null && preg_match("/\\\$ref:\s*'([^']+)'/", $line, $m)) {
            $routes[$currentRoute] = $m[1];
            $currentRoute = null;
        }
    }
    return $routes;
}

/** @return array<string,array<string,array<string,string>>> file => pathKey => [method => blockText] */
function parsePathFiles(string $dir): array
{
    $result = [];
    foreach (glob($dir . '/*.yaml') ?: [] as $file) {
        $lines = file($file, FILE_IGNORE_NEW_LINES) ?: [];
        $topKey = null;
        $method = null;
        $block = [];
        $ops = [];
        $flush = function () use (&$ops, &$topKey, &$method, &$block) {
            if ($topKey !== null && $method !== null) {
                $ops[$topKey][$method] = implode("\n", $block);
            }
            $method = null;
            $block = [];
        };
        foreach ($lines as $line) {
            if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)\s*:\s*(?:#.*)?$/', $line, $m)) {
                $flush();
                $topKey = $m[1];
                continue;
            }
            if ($topKey === null || trim($line) === '' || ltrim($line)[0] === '#') {
                continue;
            }
            $indent = strlen($line) - strlen(ltrim($line, ' '));
            if ($indent <= 2 && preg_match('/^\s{2}(get|post|put|delete|patch)\s*:\s*$/', $line, $m)) {
                $flush();
                $method = $m[1];
                $block = [];
                continue;
            }
            if ($method !== null && $indent > 2) {
                $block[] = $line;
            } elseif ($method !== null && $indent <= 2 && trim($line) !== '') {
                $flush();
            }
        }
        $flush();
        $result[basename($file)] = $ops;
    }
    return $result;
}

function collectRefs(string $dir): array
{
    $refs = [];
    foreach (array_merge(glob($dir . '/*.yaml') ?: [], glob($dir . '/components/*/*.yaml') ?: []) as $file) {
        $text = file_get_contents($file) ?: '';
        if (preg_match_all("/\\\$\s*ref\s*:\s*'([^']+)'/", $text, $m)) {
            foreach ($m[1] as $ref) {
                $refs[] = [$file, $ref];
            }
        }
    }
    return $refs;
}

/* ---------------- L2/L3: operations ---------------- */

$pathFilesDir = $base . '/paths';
$pathFiles = parsePathFiles($pathFilesDir);

$allOpIds = [];
foreach ($pathFiles as $fname => $ops) {
    foreach ($ops as $pathKey => $methods) {
        if ($methods === []) {
            $errors[] = "L2|$fname#{$pathKey}: 无任何 HTTP method 定义";
            continue;
        }
        foreach ($methods as $httpMethod => $block) {
            $loc = "{$fname}#{$pathKey}.{$httpMethod}";
            if (!preg_match('/^\s*operationId\s*:\s*([A-Za-z0-9_]+)\s*$/m', $block, $m)) {
                $errors[] = "L2|{$loc}: 缺 operationId";
            } else {
                $opId = $m[1];
                if (!preg_match('/^[a-z][a-z0-9_]*$/', $opId)) {
                    $errors[] = "L2|{$loc}: operationId 格式不合规 {$opId}";
                }
                if (isset($allOpIds[$opId])) {
                    $errors[] = "L2|{$loc}: operationId 重复 {$opId}（已在 {$allOpIds[$opId]} 定义）";
                }
                $allOpIds[$opId] = $loc;
            }
            if (!preg_match('/^\s*tags\s*:/m', $block)) {
                $errors[] = "L2|{$loc}: 缺 tags";
            }
            if (!preg_match("/'(200|201|202)'\s*:/", $block)) {
                $errors[] = "L2|{$loc}: responses 缺 2xx";
            }
            if (!preg_match('/^\s*security\s*:/m', $block)) {
                $errors[] = "L2|{$loc}: 缺 security 声明（公开端点须显式 security: []）";
            }
            if ($httpMethod === 'post' && !str_contains($block, 'Idempotency-Key')) {
                $errors[] = "L3|{$loc}: post 缺 Idempotency-Key 参数引用";
            }
        }
    }
}

/* ---------------- L1: main <-> files 双向覆盖 ---------------- */

$mainFile = $base . '/gainode-v2.yaml';
$mainRoutes = parseMainPaths($mainFile);
if ($mainRoutes === []) {
    $errors[] = 'L1|gainode-v2.yaml: paths 段未解析到任何路由';
}
$referencedKeys = [];
foreach ($mainRoutes as $route => $ref) {
    if (!preg_match("~^\./paths/([a-z_]+\.yaml)#/([A-Za-z0-9_]+)$~", $ref, $m)) {
        $errors[] = "L1|{$route}: ref 格式异常 {$ref}";
        continue;
    }
    $fname = $m[1];
    $pkey = $m[2];
    $referencedKeys[$fname][] = $pkey;
    if (!isset($pathFiles[$fname])) {
        $errors[] = "L1|{$route}: 目标文件不存在 paths/{$fname}";
        continue;
    }
    if (!isset($pathFiles[$fname][$pkey])) {
        $errors[] = "L1|{$route}: paths/{$fname} 不含路径项 {$pkey}";
    }
}
foreach ($pathFiles as $fname => $ops) {
    $used = $referencedKeys[$fname] ?? [];
    foreach (array_keys($ops) as $pkey) {
        if (!in_array($pkey, $used, true)) {
            $warnings[] = "L1|paths/{$fname}#{$pkey}: 未被主文档引用（孤儿路径项）";
        }
    }
}

/* ---------------- L4: $ref 解析 ---------------- */

foreach (collectRefs($base) as [$file, $ref]) {
    if (!str_contains($ref, '#')) {
        $errors[] = "L4|" . basename($file) . ": ref 缺 # 指针 {$ref}";
        continue;
    }
    [$relFile, $pointer] = explode('#', $ref, 2);
    if (trim($relFile) === '') {
        // 同文件内部引用（#/Key）
        $target = realpath($file);
    } else {
        $target = realpath(dirname($file) . '/' . $relFile);
    }
    if ($target === false || is_dir($target)) {
        $errors[] = "L4|" . basename($file) . ": ref 文件不存在 {$ref}";
        continue;
    }
    $key = basename(str_replace('/', '#', $pointer));
    $segments = array_values(array_filter(explode('/', $pointer), static fn ($s) => $s !== ''));
    if ($segments === []) {
        continue;
    }
    $lastKey = end($segments);
    $targetText = file_get_contents($target) ?: '';
    $keyLines = preg_grep("/^[\s]*'?\"?{$lastKey}'?\"?\s*:/m", explode("\n", $targetText)) ?: [];
    if ($keyLines === []) {
        $errors[] = "L4|" . basename($file) . ": ref 末段 key 未找到 {$ref}";
    }
}

/* ---------------- L5: 种子 SQL ↔ OpenAPI 对账 ---------------- */

$seedFile = dirname($base) . '/sql/20260820_v2_api_routes_seed.sql';
if (is_file($seedFile)) {
    $raw = file_get_contents($seedFile) ?: '';
    // UTF-16LE（BOM FF FE）→ UTF-8
    if (str_starts_with($raw, "\xFF\xFE")) {
        $raw = substr($raw, 2);
        $converted = function_exists('mb_convert_encoding')
            ? mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE')
            : (function_exists('iconv') ? iconv('UTF-16LE', 'UTF-8', $raw) : null);
        $raw = is_string($converted) ? $converted : '';
    }
    if ($raw === '') {
        $errors[] = 'L5|seed: 无法解码（缺 mbstring/iconv 或文件为空）';
    } else {
        $seedRows = [];
        $seenKeys = [];
        if (preg_match_all("/\('([0-9a-f]{32})','api_v2','[^']*','[^']*','(GET|POST|PUT|DELETE)',NULL,'([^']*)'/", $raw, $m, PREG_SET_ORDER)) {
            foreach ($m as $row) {
                [, $key, $method, $url] = $row;
                if (isset($seenKeys[$key])) {
                    $errors[] = "L5|seed: 重复路由 key {$key}（{$seenKeys[$key]} 与 {$method} {$url}），ON DUPLICATE 将吞路由";
                }
                $seenKeys[$key] = "{$method} {$url}";
                $seedRows["{$method} /api/v1{$url}"] = true;
            }
        } else {
            $errors[] = 'L5|seed: 未解析出任何 INSERT 行';
        }

        $openapiOps = [];
        foreach ($mainRoutes as $route => $ref) {
            $fname = basename(explode('#', $ref)[0]);
            $pkey = ltrim(explode('#', $ref)[1] ?? '', '/');
            foreach (array_keys($pathFiles[$fname][$pkey] ?? []) as $hm) {
                $openapiOps[strtoupper($hm) . ' ' . $route] = true;
            }
        }
        foreach (array_keys($seedRows) as $k) {
            if (!isset($openapiOps[$k])) {
                $errors[] = "L5|seed↔OpenAPI: 种子路由在 OpenAPI 中不存在 {$k}";
            }
        }
        $cEndCount = 0;
        foreach (array_keys($openapiOps) as $k) {
            if (str_starts_with($k, 'GET ') || !str_contains($k, '/admin')) {
                $cEndCount++;
            }
        }
        $warnings[] = 'L5|info: seed 行数=' . count($seedRows) . '，OpenAPI 总 operation 数=' . count($openapiOps) . '（admin_v2 路由另行注册，不在本种子内，缺失仅提示不判失败）';
    }
} else {
    $warnings[] = 'L5|seed 文件不存在，跳过对账';
}

/* ---------------- 输出 ---------------- */

echo "==== OpenAPI lint ====\n";
echo sprintf("operations=%d | routes(main)=%d | path-files=%d\n", count($allOpIds), count($mainRoutes), count($pathFiles));
foreach ($warnings as $w) {
    echo "[WARN] {$w}\n";
}
if ($errors === []) {
    echo "RESULT: PASS — 0 error\n";
    exit(0);
}
foreach ($errors as $e) {
    echo "[ERROR] {$e}\n";
}
echo sprintf("RESULT: FAIL — %d error(s)\n", count($errors));
exit(1);
