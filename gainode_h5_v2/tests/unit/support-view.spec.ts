import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'

const pushMock = vi.hoisted(() => vi.fn())
vi.mock('vue-router', () => ({
  useRouter: () => ({ push: pushMock, replace: vi.fn() }),
  useRoute: () => ({ params: {}, query: {} }),
}))

import SupportRoot from '../../src/views/support/m-support-001/index.vue'
import SupportNew from '../../src/views/support/m-support-002/index.vue'
import SupportDetail from '../../src/views/support/m-support-003/index.vue'

const RouterLinkStub = {
  props: ['to'],
  template: '<a :href="typeof to === \'string\' ? to : to.path"><slot /></a>',
}

beforeEach(() => {
  setActivePinia(createPinia())
  pushMock.mockReset()
})

describe('M-SUPPORT-001 帮助中心 / 工单列表', () => {
  it('渲染标题 + 创建工单 fail-closed（disabled）+ FAQ/工单受限', () => {
    const w = mount(SupportRoot, { global: { stubs: { RouterLink: RouterLinkStub } } })
    expect(w.find('[data-testid="create"]').exists()).toBe(true)
    expect(w.find('[data-testid="create-btn"]').attributes('disabled')).toBeDefined()
    expect(w.find('[data-testid="faq"]').text()).toContain('常见问题')
    expect(w.find('[data-testid="tickets"]').text()).toContain('我的工单')
  })
})

describe('M-SUPPORT-002 创建工单 / 申诉（Restricted）', () => {
  it('渲染受限态 + 返回按钮回 /support', async () => {
    const w = mount(SupportNew, { global: { stubs: { RouterLink: RouterLinkStub } } })
    expect(w.text()).toContain('创建工单')
    await w.find('[data-testid="back"]').trigger('click')
    expect(pushMock).toHaveBeenCalledWith('/support')
  })
})

describe('M-SUPPORT-003 工单详情（Restricted）', () => {
  it('渲染受限态 + 返回按钮回 /support', async () => {
    const w = mount(SupportDetail, { global: { stubs: { RouterLink: RouterLinkStub } } })
    expect(w.text()).toContain('工单详情')
    await w.find('[data-testid="back"]').trigger('click')
    expect(pushMock).toHaveBeenCalledWith('/support')
  })
})
