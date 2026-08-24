$ErrorActionPreference = 'Stop'
Set-Location 'E:\github\sports\gainode_h5_v2'
$dirs = Get-ChildItem src\views -Recurse -Directory | Where-Object { $_.Name -match '^m-' }
$done = @(); $skipped = @()
foreach ($d in $dirs) {
    $f = Join-Path $d.FullName 'index.vue'
    if (-not (Test-Path $f)) { $skipped += "$($d.Name):NO-INDEX"; continue }
    $t = [IO.File]::ReadAllText($f)
    if ($t -match 'DataStateBadge') { $skipped += "$($d.Name):ALREADY"; continue }
    $pageId = $d.Name.ToUpper()
    $lines = [System.Collections.Generic.List[string]]([IO.File]::ReadAllLines($f))

    # 1. import after last top-level import line
    $lastImport = -1
    for ($i = 0; $i -lt $lines.Count; $i++) {
        if ($lines[$i] -match "^import .+$") { $lastImport = $i }
        if ($lines[$i] -match '^</script>') { break }
    }
    if ($lastImport -lt 0) { $skipped += "$($d.Name):NO-IMPORT"; continue }
    # relative depth: src/views/<domain>/<page>/index.vue -> ../../../components/
    $rel = '../../../components/DataStateBadge.vue'
    $lines.Insert($lastImport + 1, "import DataStateBadge from '$rel'")

    # 2. badge element: after first '</h1>' line, else inside AuthShell root
    $badgeAt = -1
    for ($i = 0; $i -lt $lines.Count; $i++) {
        if ($lines[$i] -match '</h1>') { $badgeAt = $i + 1; break }
    }
    if ($badgeAt -lt 0) {
        for ($i = 0; $i -lt $lines.Count; $i++) {
            if ($lines[$i] -match '<AuthShell') {
                $j = $i
                while ($j -lt $lines.Count -and $lines[$j] -notmatch '>\s*$') { $j++ }
                $badgeAt = $j + 1; break
            }
        }
    }
    if ($badgeAt -lt 0) { $skipped += "$($d.Name):NO-ANCHOR"; continue }
    $indent = ([regex]::Match($lines[$badgeAt - 1], '^\s*')).Value
    $lines.Insert($badgeAt, "$indent<DataStateBadge page-id=`"$pageId`" />")

    [IO.File]::WriteAllLines($f, $lines, [Text.UTF8Encoding]::new($false))
    $done += $pageId
}
Write-Host "integrated: $($done.Count)"
$done | Sort-Object | ForEach-Object { $_ } | Out-String -Width 200
Write-Host "--- skipped ---"
$skipped
