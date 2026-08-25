import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'

const pushMock = vi.hoisted(() => vi.fn())
vi.mock('vue-router', () => ({
  useRouter: () => ({ push: pushMock, replace: vi.fn() }),
  useRoute: () => ({ params: {}, query: {} }),
}))

import AiSignals from '../../src/views/ai/m-ai-001/index.vue'
import Growth from '../../src/views/growth/m-growth-001/index.vue'
import PredictFree from '../../src/views/prediction/m-predict-free-001/index.vue'
import Migration from '../../src/views/migration/m-migration-001/index.vue'

const RouterLinkStub = {
  props: ['to'],
  template: '<a :href="typeof to === \'string\' ? to : to.path"><slot /></a>',
}

function mountView(comp: unknown) {
  return mount(comp as never, { global: { stubs: { RouterLink: RouterLinkStub } } })
}

beforeEach(() => {
  setActivePinia(createPinia())
  pushMock.mockReset()
})

describe('M-AI-001 AI 信号原型页（P1，READ_ONLY）', () => {
  it('统计概览 + 信号卡列表 + 徽标 + 返回首页', async () => {
    const w = mountView(AiSignals)
    expect(w.findComponent({ name: 'DataStateBadge' }).exists()).toBe(true)
    expect(w.findAll('.stat-card').length).toBe(3)
    const cards = w.findAll('.signal-card')
    expect(cards.length).toBeGreaterThanOrEqual(5)
    expect(w.text()).toContain('bps')
    expect(w.text()).toContain('置信度')
    await w.find('[data-testid="back"]').trigger('click')
    expect(pushMock).toHaveBeenCalledWith('/')
  })

  it('每张信号卡含方向与状态标签', () => {
    const w = mountView(AiSignals)
    expect(w.find('.dir.long').exists()).toBe(true)
    expect(w.find('.dir.short').exists()).toBe(true)
    expect(w.find('.status.live').exists()).toBe(true)
  })
})

describe('M-GROWTH-001 团队邀请原型页（P1，DEFERRED）', () => {
  it('邀请码 + 分层统计 + 成员表 + 返回我的', async () => {
    const w = mountView(Growth)
    expect(w.findComponent({ name: 'DataStateBadge' }).exists()).toBe(true)
    expect(w.find('[data-testid="copy-invite"]').exists()).toBe(true)
    expect(w.findAll('.level-card').length).toBe(3)
    expect(w.findAll('tbody tr').length).toBeGreaterThanOrEqual(5)
    // 复制按钮交互
    await w.find('[data-testid="copy-invite"]').trigger('click')
    expect(w.text()).toContain('已复制')
    await w.find('[data-testid="back"]').trigger('click')
    expect(pushMock).toHaveBeenCalledWith('/me')
  })
})

describe('M-PREDICT-FREE-001 免费预测原型页（P1，DEFERRED）', () => {
  it('竞猜卡渲染 + 投票交互锁定单次 + 返回竞猜', async () => {
    const w = mountView(PredictFree)
    expect(w.findComponent({ name: 'DataStateBadge' }).exists()).toBe(true)
    const cards = w.findAll('.poll-card')
    expect(cards.length).toBe(3)

    const first = cards[0]
    await first.find('[data-testid="opt-0"]').trigger('click')
    expect(first.text()).toContain('已投票')

    // 未投票的卡片可继续投；已投卡片重复点击不叠加
    const before = first.find('.opt.mine').exists()
    await first.find('[data-testid="opt-0"]').trigger('click')
    expect(first.find('.opt.mine').exists()).toBe(before)

    await w.find('[data-testid="back"]').trigger('click')
    expect(pushMock).toHaveBeenCalledWith('/prediction')
  })
})

describe('M-MIGRATION-001 APT-I→APT-C 迁移（Future/CLOSED，保持受限壳不变）', () => {
  it('关闭态 + 返回资产', async () => {
    const w = mountView(Migration)
    expect(w.find('[data-testid="fs-restricted"]').exists()).toBe(true)
    expect(w.text()).toContain('迁移')
    await w.find('[data-testid="back"]').trigger('click')
    expect(pushMock).toHaveBeenCalledWith('/asset')
  })
})
