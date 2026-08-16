import { beforeEach, describe, expect, it, vi } from 'vitest'

const getMock = vi.hoisted(() => vi.fn())
vi.mock('../../src/api/http', () => ({
  get: getMock,
  post: vi.fn(),
}))

import { otcApi } from '../../src/api/otc'
import { setActivePinia, createPinia } from 'pinia'
import { useOtcStore } from '../../src/stores/otc'

beforeEach(() => {
  getMock.mockReset()
  getMock.mockResolvedValue({ request_id: 'r1', data: {} })
  setActivePinia(createPinia())
})

describe('OTC 领域 API 绑定路径（只读）', () => {
  it('otcApi.orderBook → GET /api/v1/otc/order-book', async () => {
    await otcApi.orderBook()
    expect(getMock).toHaveBeenCalledWith('/api/v1/otc/order-book')
  })

  it('otcApi.orderDetail → GET /api/v1/otc/orders/{id}', async () => {
    await otcApi.orderDetail('o1')
    expect(getMock).toHaveBeenCalledWith('/api/v1/otc/orders/o1')
  })

  it('otcApi.trades → GET /api/v1/otc/trades', async () => {
    await otcApi.trades()
    expect(getMock).toHaveBeenCalledWith('/api/v1/otc/trades')
  })

  it('otcApi.myOrders → GET /api/v1/otc/users/me/orders（me 占位）', async () => {
    await otcApi.myOrders()
    expect(getMock).toHaveBeenCalledWith('/api/v1/otc/users/me/orders')
  })
})

describe('useOtcStore', () => {
  it('fetchOrderBook 写入订单簿', async () => {
    getMock.mockResolvedValue({
      request_id: 'r1',
      data: [
        { otc_order_id: 'o1', user_id: 'u1', side: 'BUY', status: 'matching', price: '1.5', quantity_apt: '10' },
      ],
    })
    const store = useOtcStore()
    await store.fetchOrderBook()
    expect(store.orderBook.length).toBe(1)
    expect(store.orderBook[0].side).toBe('BUY')
  })

  it('fetchMyOrders 写入订单列表', async () => {
    getMock.mockResolvedValue({
      request_id: 'r1',
      data: [{ otc_order_id: 'o2', user_id: 'u1', side: 'SELL', status: 'partial' }],
    })
    const store = useOtcStore()
    await store.fetchMyOrders()
    expect(store.orders.length).toBe(1)
  })

  it('fetchOrderDetail 写入单笔订单', async () => {
    getMock.mockResolvedValue({
      request_id: 'r1',
      data: { otc_order_id: 'o3', user_id: 'u1', side: 'SELL', status: 'completed', power_frozen: '5' },
    })
    const store = useOtcStore()
    await store.fetchOrderDetail('o3')
    expect(store.order?.otc_order_id).toBe('o3')
    expect(store.order?.power_frozen).toBe('5')
  })

  it('tradesByOrder 按订单过滤并正序', async () => {
    getMock.mockResolvedValue({
      request_id: 'r1',
      data: [
        { trade_id: 't1', otc_order_id: 'o1', buyer_user_id: 'b', seller_user_id: 's', status: 'completed', created_time: 20 },
        { trade_id: 't2', otc_order_id: 'o1', buyer_user_id: 'b', seller_user_id: 's', status: 'completed', created_time: 10 },
        { trade_id: 't3', otc_order_id: 'oX', buyer_user_id: 'b', seller_user_id: 's', status: 'completed', created_time: 5 },
      ],
    })
    const store = useOtcStore()
    await store.fetchTrades()
    const trades = store.tradesByOrder('o1')
    expect(trades.length).toBe(2)
    expect(trades[0].trade_id).toBe('t2')
    expect(trades[1].trade_id).toBe('t1')
  })

  it('fetch 失败 → 错误信息写入对应字段', async () => {
    getMock.mockRejectedValue(new Error('boom'))
    const store = useOtcStore()
    await store.fetchOrderBook()
    expect(store.bookError).toBe('boom')
  })
})
