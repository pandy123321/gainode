import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'

const pushMock = vi.hoisted(() => vi.fn())
vi.mock('vue-router', () => ({
  useRouter: () => ({ push: pushMock }),
}))

const robotSummaryMock = vi.hoisted(() => vi.fn())
vi.mock('../../src/api/robot', () => ({ robotApi: { summary: robotSummaryMock } }))

const marketsMock = vi.hoisted(() => vi.fn())
vi.mock('../../src/api/prediction', () => ({ predictionApi: { markets: marketsMock } }))

const balanceMock = vi.hoisted(() => vi.fn())
vi.mock('../../src/api/asset', () => ({ assetApi: { balance: balanceMock } }))

const eligibilityMeMock = vi.hoisted(() => vi.fn())
vi.mock('../../src/api/kyc', () => ({ eligibilityApi: { me: eligibilityMeMock } }))

const noticeListMock = vi.hoisted(() => vi.fn())
vi.mock('../../src/api/notice', () => ({ noticeApi: { list: noticeListMock } }))

import HomeView from '../../src/views/home/m-home-001/index.vue'

const RouterLinkStub = {
  props: ['to'],
  template: '<a :href="typeof to === \'string\' ? to : to.path"><slot /></a>',
}

function mountHome() {
  return mount(HomeView, {
    global: { stubs: { RouterLink: RouterLinkStub } },
  })
}

beforeEach(() => {
  setActivePinia(createPinia())
  pushMock.mockReset()
  robotSummaryMock.mockReset()
  marketsMock.mockReset()
  balanceMock.mockReset()
  eligibilityMeMock.mockReset()
  noticeListMock.mockReset()

  robotSummaryMock.mockResolvedValue({
    request_id: 'r1',
    data: { robot_id: 'rb1', level: 3, status: 'active', standard_capacity: '100' },
  })
  marketsMock.mockResolvedValue({
    request_id: 'r1',
    data: [{ market_id: 'm1', event_id: 'e1', template_id: 'FOOTBALL_PREMATCH_1X2', market_status: 'open', selections: ['HOME', 'DRAW', 'AWAY'] }],
  })
  balanceMock.mockResolvedValue({
    request_id: 'r1',
    data: { account_id: 'a1', balance_apt_i: '10.5', effective_available: '10.5' },
  })
  eligibilityMeMock.mockResolvedValue({
    request_id: 'r1',
    data: {
      user_id: 'u1',
      global_p: { feature_key: 'global_p', allowed: true },
      ai: { feature_key: 'ai', allowed: true },
      prediction: { feature_key: 'prediction', allowed: true },
    },
  })
  noticeListMock.mockResolvedValue({ request_id: 'r1', data: [] })
})

describe('M-HOME-001 首页', () => {
  it('正常态渲染各模块卡片（Robot/竞猜/资产/榜单）', async () => {
    const w = mountHome()
    await flushPromises()
    expect(w.find('[data-testid="home-robot"]').exists()).toBe(true)
    expect(w.find('[data-testid="home-markets"]').exists()).toBe(true)
    expect(w.find('[data-testid="home-ai"]').exists()).toBe(true)
    expect(w.find('[data-testid="home-leaderboard"]').exists()).toBe(true)
    expect(w.find('[data-testid="home-robot"]').text()).toContain('Lv.3')
  })

  it('准入受限 → 主 CTA 为「进入 KYC」且 Hero 显示受限', async () => {
    eligibilityMeMock.mockResolvedValue({
      request_id: 'r1',
      data: {
        user_id: 'u1',
        global_p: { feature_key: 'global_p', allowed: false },
        ai: { feature_key: 'ai', allowed: false },
        prediction: { feature_key: 'prediction', allowed: false },
      },
    })
    const w = mountHome()
    await flushPromises()
    expect(w.find('.cta').text()).toContain('KYC')
    expect(w.find('.hero').attributes('data-restricted')).toBe('true')
  })

  it('单卡失败不拖垮整页（资产失败，Robot 仍渲染数据）', async () => {
    balanceMock.mockRejectedValue(new Error('net down'))
    const w = mountHome()
    await flushPromises()
    // Robot 卡仍有数据
    expect(w.find('[data-testid="home-robot"]').text()).toContain('Lv.3')
    // 资产卡进入 error 态，不显示数值 0
    expect(w.find('[data-testid="home-ai"]').text()).not.toContain('0.00')
    expect(w.find('[data-testid="fs-error"]').exists()).toBe(true)
  })

  it('热门竞猜为空 → 显示空态而非 0', async () => {
    marketsMock.mockResolvedValue({ request_id: 'r1', data: [] })
    const w = mountHome()
    await flushPromises()
    expect(w.find('[data-testid="home-markets"]').text()).toContain('暂无热门竞猜')
  })

  it('主 CTA 点击导航到 /robot', async () => {
    const w = mountHome()
    await flushPromises()
    await w.find('.cta').trigger('click')
    expect(pushMock).toHaveBeenCalledWith('/robot')
  })
})
