import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'

const pushMock = vi.hoisted(() => vi.fn())
vi.mock('vue-router', () => ({
  useRouter: () => ({ push: pushMock }),
  useRoute: () => ({ params: { id: 'o1' } }),
}))

const orderBookMock = vi.hoisted(() => vi.fn())
const orderDetailMock = vi.hoisted(() => vi.fn())
const tradesMock = vi.hoisted(() => vi.fn())
const myOrdersMock = vi.hoisted(() => vi.fn())
vi.mock('../../src/api/otc', () => ({
  otcApi: {
    orderBook: orderBookMock,
    orderDetail: orderDetailMock,
    trades: tradesMock,
    myOrders: myOrdersMock,
  },
}))

const positionMock = vi.hoisted(() => vi.fn())
vi.mock('../../src/api/power', () => ({
  powerApi: { position: positionMock },
}))

import OtcRoot from '../../src/views/otc/m-otc-001/index.vue'
import OtcInput from '../../src/views/otc/m-otc-002/index.vue'
import OtcConfirm from '../../src/views/otc/m-otc-003/index.vue'
import OtcResult from '../../src/views/otc/m-otc-004/index.vue'
import OtcMyOrders from '../../src/views/otc/m-otc-005/index.vue'
import OtcDetail from '../../src/views/otc/m-otc-006/index.vue'

const RouterLinkStub = {
  props: ['to'],
  template: '<a :href="typeof to === \'string\' ? to : to.path"><slot /></a>',
}

beforeEach(() => {
  setActivePinia(createPinia())
  pushMock.mockReset()
  orderBookMock.mockReset()
  orderDetailMock.mockReset()
  tradesMock.mockReset()
  myOrdersMock.mockReset()
  positionMock.mockReset()

  orderBookMock.mockResolvedValue({ request_id: 'r1', data: [] })
  orderDetailMock.mockResolvedValue({ request_id: 'r1', data: null })
  tradesMock.mockResolvedValue({ request_id: 'r1', data: [] })
  myOrdersMock.mockResolvedValue({ request_id: 'r1', data: [] })
  positionMock.mockResolvedValue({
    request_id: 'r1',
    data: { user_id: 'u1', available: '30', limit: '100' },
  })
})

describe('M-OTC-001 OTC 市场', () => {
  it('挂买/挂卖 fail-closed（disabled）', async () => {
    const w = mount(OtcRoot, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="otc-buy"]').attributes('disabled')).toBeDefined()
    expect(w.find('[data-testid="otc-sell"]').attributes('disabled')).toBeDefined()
    expect(w.text()).toContain('OTC')
  })

  it('订单簿只读渲染', async () => {
    orderBookMock.mockResolvedValue({
      request_id: 'r1',
      data: [{ otc_order_id: 'o1', user_id: 'u1', side: 'BUY', status: 'matching', price: '1.5', quantity_apt: '10' }],
    })
    const w = mount(OtcRoot, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="otc-orderbook"]').text()).toContain('1.5')
  })

  it('无订单簿 → 空态', async () => {
    const w = mount(OtcRoot, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="orderbook-empty"]').exists()).toBe(true)
  })
})

describe('M-OTC-002/003/004 挂单输入/确认/结果（Restricted）', () => {
  it.each([
    [OtcInput, 'm_otc_002'],
    [OtcConfirm, 'm_otc_003'],
    [OtcResult, 'm_otc_004'],
  ])('受限占位渲染 + 返回 OTC 市场', async (Comp) => {
    const w = mount(Comp, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="back"]').exists()).toBe(true)
    w.find('[data-testid="back"]').trigger('click')
    expect(pushMock).toHaveBeenCalledWith('/otc')
  })
})

describe('M-OTC-005 我的 OTC 订单', () => {
  it('渲染订单列表', async () => {
    myOrdersMock.mockResolvedValue({
      request_id: 'r1',
      data: [
        { otc_order_id: 'o1', user_id: 'u1', side: 'SELL', status: 'partial', quantity_apt: '10', filled_quantity_apt: '4', remaining_quantity_apt: '6' },
        { otc_order_id: 'o2', user_id: 'u1', side: 'BUY', status: 'completed', quantity_apt: '3' },
      ],
    })
    const w = mount(OtcMyOrders, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.findAll('[data-testid="otc-order-row"]').length).toBe(2)
    expect(w.text()).toContain('部分成交')
  })

  it('无订单 → 空态', async () => {
    const w = mount(OtcMyOrders, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="otc-orders-empty"]').exists()).toBe(true)
  })
})

describe('M-OTC-006 OTC 订单详情', () => {
  it('渲染订单事实 + Sell Power 影响', async () => {
    orderDetailMock.mockResolvedValue({
      request_id: 'r1',
      data: {
        otc_order_id: 'o1',
        user_id: 'u1',
        side: 'SELL',
        status: 'partial',
        price: '1.5',
        quantity_apt: '10',
        filled_quantity_apt: '4',
        remaining_quantity_apt: '6',
        fee_apt: '0.1',
        power_required: '12',
        power_frozen: '8',
        power_consumed: '4',
      },
    })
    const w = mount(OtcDetail, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="otc-detail"]').exists()).toBe(true)
    expect(w.find('[data-testid="otc-facts"]').text()).toContain('1.5')
    expect(w.find('[data-testid="otc-power"]').text()).toContain('12')
  })

  it('成交记录渲染（按订单过滤）', async () => {
    orderDetailMock.mockResolvedValue({
      request_id: 'r1',
      data: { otc_order_id: 'o1', user_id: 'u1', side: 'BUY', status: 'completed', quantity_apt: '10' },
    })
    tradesMock.mockResolvedValue({
      request_id: 'r1',
      data: [
        { trade_id: 't1', otc_order_id: 'o1', buyer_user_id: 'b', seller_user_id: 's', status: 'completed', quantity_apt: '4', price_apt: '1.5', created_time: 10 },
      ],
    })
    const w = mount(OtcDetail, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="otc-trades"]').text()).toContain('4')
  })

  it('取消订单 fail-closed（disabled）', async () => {
    orderDetailMock.mockResolvedValue({
      request_id: 'r1',
      data: { otc_order_id: 'o1', user_id: 'u1', side: 'BUY', status: 'matching', quantity_apt: '10' },
    })
    const w = mount(OtcDetail, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="otc-cancel"]').attributes('disabled')).toBeDefined()
  })

  it('订单不存在 → 未找到空态', async () => {
    orderDetailMock.mockResolvedValue({ request_id: 'r1', data: null })
    const w = mount(OtcDetail, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="otc-not-found"]').exists()).toBe(true)
  })
})
