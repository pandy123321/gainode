/**
 * NEXT-02 步骤① 防漂移测试（设计稿 §3）
 * 1) 路由表每个 pageId 必须在 PAGE_STATES 注册（防漏页）
 * 2) PAGE_STATES 无死键（防孤儿键）；state 必属枚举；FAIL_CLOSED 必带非空 note
 * 3) 徽标可见性规则：REAL_DATA 不渲染，其余四态渲染且配色类正确
 * 4) 抽样 5 个 FAIL_CLOSED 页挂载断言 badge 与 note 文案（+ M-AI-001 整页集成）
 * 5) 全部 44 个 m-xx 页面 index.vue 已接入 DataStateBadge 且 page-id 双向对齐
 */
import { describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { readFileSync, readdirSync, statSync } from 'node:fs'
import { dirname, join, resolve } from 'node:path'
import { fileURLToPath } from 'node:url'
import { PAGE_DATA_STATES, PAGE_STATES } from '../../src/pageStates'
import DataStateBadge from '../../src/components/DataStateBadge.vue'

vi.mock('vue-router', () => ({
  useRouter: () => ({ push: vi.fn() }),
}))

const PROJECT_ROOT = resolve(dirname(fileURLToPath(import.meta.url)), '../..')

/** 从路由表源码提取全部 meta.pageId（单一事实来源） */
function extractRoutePageIds(): string[] {
  const src = readFileSync(join(PROJECT_ROOT, 'src/router/index.ts'), 'utf8')
  return [...src.matchAll(/pageId:\s*'([^']+)'/g)].map((m) => m[1])
}

/** 递归收集 src/views 各分组下 m-xx 页面的 index.vue */
function collectMPageFiles(dir: string): string[] {
  const out: string[] = []
  for (const name of readdirSync(dir)) {
    const p = join(dir, name)
    if (statSync(p).isDirectory()) {
      if (/^m-/.test(name)) {
        const idx = join(p, 'index.vue')
        try {
          statSync(idx)
          out.push(idx)
        } catch {
          /* m-* 目录缺 index.vue 由后续断言暴露 */
        }
        continue
      }
      out.push(...collectMPageFiles(p))
    }
  }
  return out
}

describe('五态注册表 · 双向校验', () => {
  const routeIds = extractRoutePageIds()
  const registryKeys = Object.keys(PAGE_STATES)

  it('路由表中每个 Page ID 均已在 PAGE_STATES 注册（防漏页）', () => {
    expect(routeIds.length).toBeGreaterThan(0)
    for (const id of routeIds) {
      expect(PAGE_STATES[id], `路由页 ${id} 未在 PAGE_STATES 注册`).toBeDefined()
    }
  })

  it('PAGE_STATES 无死键且与路由表数量一致（防孤儿键）', () => {
    const routeSet = new Set(routeIds)
    for (const key of registryKeys) {
      expect(routeSet.has(key), `PAGE_STATES 死键 ${key} 不存在于路由表`).toBe(true)
    }
    expect(registryKeys.length).toBe(routeSet.size)
  })

  it('所有 state 均属于 PAGE_DATA_STATES 枚举', () => {
    const enumSet: ReadonlySet<string> = new Set(PAGE_DATA_STATES)
    for (const [key, entry] of Object.entries(PAGE_STATES)) {
      expect(enumSet.has(entry.state), `${key} 的 state "${entry.state}" 非法`).toBe(true)
    }
  })

  it('FAIL_CLOSED 必带非空 note；其余态 note 可选但不允许空串', () => {
    for (const [key, entry] of Object.entries(PAGE_STATES)) {
      if (entry.state === 'FAIL_CLOSED') {
        expect(entry.note, `FAIL_CLOSED 页 ${key} 缺 note`).toBeDefined()
        expect(entry.note!.trim().length, `FAIL_CLOSED 页 ${key} note 为空`).toBeGreaterThan(0)
      } else if (entry.note !== undefined) {
        expect(entry.note.trim().length, `${key} note 为空串`).toBeGreaterThan(0)
      }
    }
  })
})

describe('DataStateBadge 可见性与配色', () => {
  it('REAL_DATA 不渲染徽标', () => {
    const w = mount(DataStateBadge, { props: { pageId: 'M-AUTH-001' } })
    expect(w.find('[data-testid="data-state-badge"]').exists()).toBe(false)
  })

  it('未注册 page-id 不渲染徽标（安全降级）', () => {
    const w = mount(DataStateBadge, { props: { pageId: 'M-NOT-EXIST-999' } })
    expect(w.find('[data-testid="data-state-badge"]').exists()).toBe(false)
  })

  it.each([
    ['M-HOME-001', 'ds-badge--read_only'],
    ['M-PREDICT-004', 'ds-badge--skeleton'],
    ['M-GROWTH-001', 'ds-badge--deferred'],
  ] as const)('%s 渲染徽标且配色类为 %s', (pageId, cls) => {
    const w = mount(DataStateBadge, { props: { pageId } })
    const badge = w.find('[data-testid="data-state-badge"]')
    expect(badge.exists()).toBe(true)
    expect(badge.classes()).toContain(cls)
  })
})

describe('抽样挂载：5 个 FAIL_CLOSED 页 badge 与 note 文案', () => {
  // 确定性均匀采样（排序后等距抽取），覆盖不同业务域，避免人工挑样
  const failClosedIds = Object.entries(PAGE_STATES)
    .filter(([, e]) => e.state === 'FAIL_CLOSED')
    .map(([k]) => k)
    .sort()

  it('FAIL_CLOSED 注册数 ≥ 5 才可抽样', () => {
    expect(failClosedIds.length).toBeGreaterThanOrEqual(5)
  })

  const step = Math.max(1, Math.floor(failClosedIds.length / 5))
  const sample = failClosedIds.filter((_, i) => i % step === 0).slice(0, 5)

  it.each(sample)('%s：badge 渲染、文案 FAIL-CLOSED、tooltip 含 note', (pageId) => {
    const entry = PAGE_STATES[pageId]
    const w = mount(DataStateBadge, { props: { pageId } })
    const badge = w.find('[data-testid="data-state-badge"]')
    expect(badge.exists()).toBe(true)
    expect(badge.classes()).toContain('ds-badge--fail_closed')
    expect(badge.text()).toContain('FAIL-CLOSED')
    expect(badge.attributes('title')).toBe(entry.note)
    expect(String(badge.attributes('title')).trim().length).toBeGreaterThan(0)
  })

  it('集成：M-AI-001 整页挂载后 badge 在页面头部渲染且 tooltip 为 note', async () => {
    const { default: AiView } = await import('../../src/views/ai/m-ai-001/index.vue')
    const w = mount(AiView)
    const badge = w.find('[data-testid="data-state-badge"]')
    expect(badge.exists()).toBe(true)
    expect(badge.classes()).toContain('ds-badge--fail_closed')
    expect(badge.attributes('title')).toBe(PAGE_STATES['M-AI-001'].note)
  })
})

describe('44 页接入防漂移（静态源码校验）', () => {
  const viewRoot = join(PROJECT_ROOT, 'src/views')

  it('每个 m-*/index.vue 已接入 <DataStateBadge> 且 page-id 已注册', () => {
    const files = collectMPageFiles(viewRoot)
    expect(files.length).toBe(44)
    const seen = new Set<string>()
    for (const f of files) {
      const content = readFileSync(f, 'utf8')
      const rel = f.slice(PROJECT_ROOT.length + 1)
      expect(content, `${rel} 未导入 DataStateBadge`).toContain('DataStateBadge')
      const m = content.match(/<DataStateBadge\s+page-id="([^"]+)"/)
      expect(m, `${rel} 根节点未加 <DataStateBadge page-id="..." />`).not.toBeNull()
      const pid = m![1]
      expect(PAGE_STATES[pid], `${rel} 的 page-id ${pid} 未注册`).toBeDefined()
      expect(seen.has(pid), `page-id ${pid} 被 ${rel} 重复使用`).toBe(false)
      seen.add(pid)
    }
  })

  it('注册表每个 m-* 键均被某页面的 page-id 引用（反向无遗漏）', () => {
    const files = collectMPageFiles(viewRoot)
    const used = new Set<string>()
    for (const f of files) {
      const m = readFileSync(f, 'utf8').match(/<DataStateBadge\s+page-id="([^"]+)"/)
      if (m) used.add(m[1])
    }
    for (const key of Object.keys(PAGE_STATES)) {
      if (key.startsWith('M-')) {
        expect(used.has(key), `注册键 ${key} 没有任何页面引用`).toBe(true)
      }
    }
  })
})
