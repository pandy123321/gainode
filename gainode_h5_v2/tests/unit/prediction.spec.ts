import { beforeEach, describe, expect, it, vi } from 'vitest'

const getMock = vi.hoisted(() => vi.fn())
vi.mock('../../src/api/http', () => ({
  get: getMock,
  post: vi.fn(),
}))

import { predictionApi } from '../../src/api/prediction'
import { setActivePinia, createPinia } from 'pinia'
import { usePredictionStore } from '../../src/stores/prediction'

beforeEach(() => {
  getMock.mockReset()
  getMock.mockResolvedValue({ request_id: 'r1', data: {} })
  setActivePinia(createPinia())
})

describe('Prediction 领域 API 绑定路径', () => {
  it('predictionApi.marketDetail → GET /api/v1/markets/{id}', async () => {
    await predictionApi.marketDetail('m1')
    expect(getMock).toHaveBeenCalledWith('/api/v1/markets/m1')
  })

  it('predictionApi.orderReceipt → GET /api/v1/orders/{id}/receipt', async () => {
    await predictionApi.orderReceipt('o1')
    expect(getMock).toHaveBeenCalledWith('/api/v1/orders/o1/receipt')
  })
})

describe('usePredictionStore', () => {
  it('fetchMarketDetail 写入 marketDetail', async () => {
    getMock.mockResolvedValue({
      request_id: 'r1',
      data: {
        market_id: 'm1',
        event_id: 'e1',
        template_id: 'FOOTBALL_PREMATCH_1X2',
        market_status: 'open',
        selections: ['HOME', 'DRAW', 'AWAY'],
      },
    })
    const store = usePredictionStore()
    await store.fetchMarketDetail('m1')
    expect(store.marketDetail?.market_id).toBe('m1')
  })

  it('fetchOrderReceipt 写入订单', async () => {
    getMock.mockResolvedValue({
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
    const store = usePredictionStore()
    await store.fetchOrderReceipt('o1')
    expect(store.orderReceipt?.order_status).toBe('settled')
  })

  it('openMarkets / closingMarkets 分区正确', async () => {
    getMock.mockResolvedValue({
      request_id: 'r1',
      data: [
        { market_id: 'm1', event_id: 'e1', template_id: 'FOOTBALL_PREMATCH_1X2', market_status: 'open' },
        { market_id: 'm2', event_id: 'e2', template_id: 'FOOTBALL_PREMATCH_1X2', market_status: 'closing' },
        { market_id: 'm3', event_id: 'e3', template_id: 'FOOTBALL_PREMATCH_1X2', market_status: 'locked' },
      ],
    })
    const store = usePredictionStore()
    await store.fetchMarkets()
    expect(store.openMarkets.map((m) => m.market_id)).toEqual(['m1'])
    expect(store.closingMarkets.map((m) => m.market_id)).toEqual(['m2'])
  })
})
