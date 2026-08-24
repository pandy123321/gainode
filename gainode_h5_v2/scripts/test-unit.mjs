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