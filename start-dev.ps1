#Requires -Version 5.1
<#
.SYNOPSIS
    One-click launcher for the Gainode V2 development stack.

.DESCRIPTION
    Starts the backend (Webman) and the two frontends (H5 + Admin), each in
    its own console window so you can watch each service's logs separately.

    Default (no switch): start all three services.
      -Backend : only the Webman backend
      -H5      : only the H5 frontend
      -Admin   : only the Admin frontend
      -Stop    : stop all Gainode dev processes

.EXAMPLE
    .\start-dev.ps1            # start all three
    .\start-dev.ps1 -Backend   # backend only
    .\start-dev.ps1 -Stop      # stop everything
#>

[CmdletBinding()]
param(
    [switch]$Backend,
    [switch]$H5,
    [switch]$Admin,
    [switch]$Stop
)

$ErrorActionPreference = 'Stop'

# ---- paths ----
$RepoRoot   = $PSScriptRoot
$BackendDir = Join-Path $RepoRoot '0.5代码\gainode后端\gainode'
$H5Dir      = Join-Path $RepoRoot 'gainode_h5_v2'
$AdminDir   = Join-Path $RepoRoot 'gainode_admin_v2'

# ---- output helpers ----
function Step { param([string]$m) Write-Host "==> $m" -ForegroundColor Cyan }
function Ok   { param([string]$m) Write-Host "    $m" -ForegroundColor Green }
function Warn { param([string]$m) Write-Host "    $m" -ForegroundColor Yellow }

function Get-Cmd {
    param([string]$Name)
    (Get-Command $Name -ErrorAction SilentlyContinue).Source
}

# ---- stop mode ----
if ($Stop) {
    Step 'Stopping Gainode dev processes ...'
    $procs = Get-CimInstance Win32_Process | Where-Object {
        ($_.Name -match 'php'  -and $_.CommandLine -match 'gainode') -or
        ($_.Name -match 'node' -and $_.CommandLine -match 'vite')
    }
    if (-not $procs) { Warn 'No Gainode dev processes found.'; exit 0 }
    foreach ($p in $procs) {
        Ok "Stopping PID $($p.ProcessId) ($($p.Name))"
        Stop-Process -Id $p.ProcessId -Force -ErrorAction SilentlyContinue
    }
    Ok 'Done.'
    exit 0
}

# ---- decide targets ----
$all       = -not ($Backend -or $H5 -or $Admin)
$doBackend = $all -or $Backend
$doH5      = $all -or $H5
$doAdmin   = $all -or $Admin

# ---- preflight checks ----
$missing = @()
if ($doBackend -and -not (Get-Cmd 'php'))  { $missing += 'php' }
if ($doH5      -and -not (Get-Cmd 'node')) { $missing += 'node' }
if ($doH5      -and -not (Get-Cmd 'npm'))  { $missing += 'npm' }
if ($doAdmin   -and -not (Get-Cmd 'node')) { $missing += 'node' }
if ($doAdmin   -and -not (Get-Cmd 'pnpm')) { $missing += 'pnpm' }
if ($missing.Count -gt 0) {
    Warn ('Missing tools: ' + (($missing | Select-Object -Unique) -join ', '))
    exit 1
}
if ($doBackend -and -not (Test-Path $BackendDir)) { Warn "Backend dir not found: $BackendDir"; exit 1 }
if ($doH5      -and -not (Test-Path $H5Dir))      { Warn "H5 dir not found: $H5Dir"; exit 1 }
if ($doAdmin   -and -not (Test-Path $AdminDir))   { Warn "Admin dir not found: $AdminDir"; exit 1 }

# ---- launch helper: open a persistent console window per service ----
function Launch {
    param([string]$Title, [string]$WorkDir, [string]$Cmd)
    Start-Process -FilePath 'powershell.exe' `
        -WorkingDirectory $WorkDir `
        -ArgumentList '-NoExit', '-NoProfile', '-Command', "Write-Host '=== $Title ===' ; $Cmd"
    Ok "Started: $Title"
}

Step 'Gainode V2 dev launcher'

if ($doBackend) {
    Step 'Backend (Webman, port 8787)'
    Warn 'Make sure MySQL (127.0.0.1:3307) and Redis (127.0.0.1:6379) are running.'
    Launch -Title 'Backend :8787' -WorkDir $BackendDir -Cmd 'php windows.php'
}

if ($doH5) {
    Step 'H5 frontend (Vite)'
    Launch -Title 'H5 (Vite)' -WorkDir $H5Dir -Cmd 'npm run dev'
}

if ($doAdmin) {
    Step 'Admin frontend (Vite, port 3030)'
    Launch -Title 'Admin (Vite :3030)' -WorkDir $AdminDir -Cmd 'pnpm dev'
}

Write-Host ''
Step 'Done. All requested services launched.'
Ok 'Stop them with: .\start-dev.ps1 -Stop'
