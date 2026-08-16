import { defineStore } from 'pinia'
import { otcApi, type OtcOrder, type OtcTrade } from '../api/otc'

interface OtcState {
  orderBook: OtcOrder[]
  orders: OtcOrder[]
  order: OtcOrder | null
  trades: OtcTrade[]

  bookLoading: boolean
  bookError: string | null
  ordersLoading: boolean
  ordersError: string | null
  detailLoading: boolean
  detailError: string | null
  tradesLoading: boolean
  tradesError: string | null
}

/** OTC 状态 —— 权威只读投影，来自服务端，不持久化；写操作 fail-closed 不提供 */
export const useOtcStore = defineStore('otc', {
  state: (): OtcState => ({
    orderBook: [],
    orders: [],
    order: null,
    trades: [],

    bookLoading: false,
    bookError: null,
    ordersLoading: false,
    ordersError: null,
    detailLoading: false,
    detailError: null,
    tradesLoading: false,
    tradesError: null,
  }),
  getters: {
    /** 该订单的成交记录（按时间正序） */
    tradesByOrder: (s) => (orderId: string): OtcTrade[] =>
      s.trades
        .filter((t) => t.otc_order_id === orderId)
        .sort((a, b) => (a.created_time ?? 0) - (b.created_time ?? 0)),
  },
  actions: {
    async fetchOrderBook() {
      this.bookLoading = true
      this.bookError = null
      try {
        const env = await otcApi.orderBook()
        this.orderBook = env.data ?? []
      } catch (e) {
        this.bookError = e instanceof Error ? e.message : '订单簿加载失败'
      } finally {
        this.bookLoading = false
      }
    },
    async fetchMyOrders() {
      this.ordersLoading = true
      this.ordersError = null
      try {
        const env = await otcApi.myOrders()
        this.orders = env.data ?? []
      } catch (e) {
        this.ordersError = e instanceof Error ? e.message : '订单列表加载失败'
      } finally {
        this.ordersLoading = false
      }
    },
    async fetchOrderDetail(id: string) {
      this.detailLoading = true
      this.detailError = null
      try {
        const env = await otcApi.orderDetail(id)
        this.order = env.data
      } catch (e) {
        this.detailError = e instanceof Error ? e.message : '订单详情加载失败'
      } finally {
        this.detailLoading = false
      }
    },
    async fetchTrades() {
      this.tradesLoading = true
      this.tradesError = null
      try {
        const env = await otcApi.trades()
        this.trades = env.data ?? []
      } catch (e) {
        this.tradesError = e instanceof Error ? e.message : '成交记录加载失败'
      } finally {
        this.tradesLoading = false
      }
    },
    reset() {
      this.orderBook = []
      this.orders = []
      this.order = null
      this.trades = []
      this.bookLoading = false
      this.bookError = null
      this.ordersLoading = false
      this.ordersError = null
      this.detailLoading = false
      this.detailError = null
      this.tradesLoading = false
      this.tradesError = null
    },
  },
})
