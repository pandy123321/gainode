$ErrorActionPreference = 'Stop'
Set-Location 'E:\github\sports\gainode_h5_v2'

# fold temp runners into a permanent harness first
New-Item -ItemType Directory -Force -Path scripts | Out-Null
$runner = @'
// EPERM-safe vitest runner for this sandbox: programmatic startVitest,
// threads pool, no config-file spawn. Usage: node scripts/test-unit.mjs [include-glob]
import { startVitest } from 'vitest/node'
const vue = (await import('@vitejs/plugin-vue')).default
const root = process.cwd()
const include = [process.argv[2] ?? 'tests/unit/**/*.spec.ts']
try {
  const v = await startVitest('test', [], {
    watch: false,
    pool: 'threads',
    environment: 'jsdom',
    include,
  }, { configFile: false, plugins: [vue()], resolve: { alias: { '@': root + '/src' } } })
  await v.close()
  process.exit(0)
} catch (e) {
  console.error(e)
  process.exit(1)
}
'@
[IO.File]::WriteAllText("$pwd\scripts\test-unit.mjs", $runner, [Text.UTF8Encoding]::new($false))
Remove-Item next02-verify.mjs, next02-verify-full.mjs -ErrorAction SilentlyContinue

Set-Location 'E:\github\sports'
git add -- gainode_h5_v2/src/pageStates.ts `
    gainode_h5_v2/src/components/DataStateBadge.vue `
    gainode_h5_v2/tests/unit/page-states.spec.ts `
    gainode_h5_v2/scripts/test-unit.mjs `
    gainode_h5_v2/src/views
git commit -m "feat(h5): page data five-state annotation system (NEXT-02 step1)

- pageStates.ts: PAGE_DATA_STATES enum + PAGE_STATES registry (45 entries:
  44 M-* pages + COMMON-RESTRICTED), each with reason note
- DataStateBadge.vue: renders badge when state != REAL_DATA; four colors +
  title tooltip carrying the note (FAIL_CLOSED)
- page-states.spec.ts: drift-guard tests x18 (route-table<->registry
  bidirectional check, enum validity, FAIL_CLOSED note required, render
  contract, full 44-page integration assertion)
- 44 m-* pages: +import +badge line (after h1 / AuthShell first child)
Evidence: vitest tests/unit = 24 files / 165 tests PASS (147 baseline kept);
vue-tsc --noEmit EXIT=0; scripts/test-unit.mjs = EPERM-safe programmatic runner"
if ($LASTEXITCODE -ne 0) { throw 'commit c9 failed' }
git log --oneline -1
