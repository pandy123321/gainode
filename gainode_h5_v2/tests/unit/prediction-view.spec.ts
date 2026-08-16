import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'

const pushMock = vi.hoisted(() => vi.fn())
vi.mock('vue-router', () => ({
  useRouter: () => ({ push: pushMock }),
  useRoute: () => ({ params: { id: 'm1' } }),
}))

const marketsMock = vi.hoisted(() => vi.fn())
const marketDetailMock = vi.hoisted(() => vi.fn())
const orderReceiptMock = vi.hoisted(() => vi.fn())
vi.mock('../../src/api/prediction', () => ({
  predictionApi: {
    markets: marketsMock,
    marketDetail: marketDetailMock,
    orderReceipt: orderReceiptMock,
  },
}))

import PredictionRoot from '../../src/views/prediction/m-predict-001/index.vue'
import PredictionOrder from '../../src/views/prediction/m-predict-005/index.vue'
import PredictionConfirm from '../../src/views/prediction/m-predict-003/index.vue'

const RouterLinkStub = {
  props: ['to'],
  template: '<a :href="typeof to === \'string\' ? to : to.path"><slot /></a>',
}

beforeEach(() => {
  setActivePinia(createPinia())
  pushMock.mockReset()
  marketsMock.mockReset()
  marketDetailMock.mockReset()
  orderReceiptMock.mockReset()

  marketsMock.mockResolvedValue({ request_id: 'r1', data: [] })
  marketDetailMock.mockResolvedValue({ request_id: 'r1', data: {} })
  orderReceiptMock.mockResolvedValue({ request_id: 'r1', data: {} })
})

describe('M-PREDICT-001 竞猜广场', () => {
  it('正常态渲染热门/即将截止分区', async () => {
    marketsMock.mockResolvedValue({
      request_id: 'r1',
      data: [
        { market_id: 'm1', event_id: 'e1', template_id: 'FOOTBALL_PREMATCH_1X2', market_status: 'open', selections: ['HOME', 'DRAW', 'AWAY'] },
        { market_id: 'm2', event_id: 'e2', template_id: 'FOOTBALL_PREMATCH_1X2', market_status: 'closing', selections: ['HOME', 'DRAW', 'AWAY'] },
      ],
    })
    const w = mount(PredictionRoot, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.findAll('[data-testid="market-card"]').length).toBe(2)
    expect(w.text()).toContain('竞猜广场')
  })

  it('无市场 → 空态', async () => {
    marketsMock.mockResolvedValue({ request_id: 'r1', data: [] })
    const w = mount(PredictionRoot, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="markets-empty"]').exists()).toBe(true)
  })
})

describe('M-PREDICT-005 订单详情', () => {
  it('渲染订单号 + 状态（赛果/结算占位，不伪造）', async () => {
    orderReceiptMock.mockResolvedValue({
      request_id: 'r1',
      data: {
        order_id: 'o1',
        user_id: 'u1',
        market_id: 'm1',
        selection: 'HOME',
        amount_apt: '10',
        order_status: 'settled',
      },
    })
    const w = mount(PredictionOrder, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="order-result"]').exists()).toBe(true)
    expect(w.text()).toContain('o1')
    expect(w.text()).toContain('10')
  })
})

describe('M-PREDICT-003 确认页 Restricted（fail-closed）', () => {
  it('显示 Restricted 占位', async () => {
    const w = mount(PredictionConfirm, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="fs-restricted"]').exists()).toBe(true)
  })
})
