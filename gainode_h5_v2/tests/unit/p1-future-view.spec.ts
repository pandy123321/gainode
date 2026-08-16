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

beforeEach(() => {
  setActivePinia(createPinia())
  pushMock.mockReset()
})

describe('M-AI-001 AI 数据详情（P1，Restricted）', () => {
  it('受限态 + 返回首页', async () => {
    const w = mount(AiSignals, { global: { stubs: { RouterLink: RouterLinkStub } } })
    expect(w.text()).toContain('AI 数据')
    expect(w.find('[data-testid="fs-restricted"]').exists()).toBe(true)
    await w.find('[data-testid="back"]').trigger('click')
    expect(pushMock).toHaveBeenCalledWith('/')
  })
})

describe('M-GROWTH-001 邀请与团队（P1，Restricted）', () => {
  it('受限态 + 返回我的', async () => {
    const w = mount(Growth, { global: { stubs: { RouterLink: RouterLinkStub } } })
    expect(w.find('[data-testid="fs-restricted"]').exists()).toBe(true)
    await w.find('[data-testid="back"]').trigger('click')
    expect(pushMock).toHaveBeenCalledWith('/me')
  })
})

describe('M-PREDICT-FREE-001 免费预测（P1/Sandbox，Restricted）', () => {
  it('受限态 + 返回竞猜', async () => {
    const w = mount(PredictFree, { global: { stubs: { RouterLink: RouterLinkStub } } })
    expect(w.find('[data-testid="fs-restricted"]').exists()).toBe(true)
    await w.find('[data-testid="back"]').trigger('click')
    expect(pushMock).toHaveBeenCalledWith('/prediction')
  })
})

describe('M-MIGRATION-001 APT-I→APT-C 迁移（Future/CLOSED）', () => {
  it('关闭态 + 返回资产', async () => {
    const w = mount(Migration, { global: { stubs: { RouterLink: RouterLinkStub } } })
    expect(w.find('[data-testid="fs-restricted"]').exists()).toBe(true)
    expect(w.text()).toContain('迁移')
    await w.find('[data-testid="back"]').trigger('click')
    expect(pushMock).toHaveBeenCalledWith('/asset')
  })
})
