import { defineStore } from 'pinia'
import {
  predictionApi,
  type PredictionMarket,
  type PredictionOrder,
} from '../api/prediction'

export interface PredictionOrderBrief {
  orderId: string
  marketId: string
  status: string
}

interface PredictionState {
  markets: PredictionMarket[]
  myOrders: PredictionOrderBrief[]
  loaded: boolean
  loading: boolean
  error: string | null
  marketDetail: PredictionMarket | null
  marketDetailLoading: boolean
  marketDetailError: string | null
  orderReceipt: PredictionOrder | null
  orderReceiptLoading: boolean
  orderReceiptError: string | null
}

export const usePredictionStore = defineStore('prediction', {
  state: (): PredictionState => ({
    markets: [],
    myOrders: [],
    loaded: false,
    loading: false,
    error: null,
    marketDetail: null,
    marketDetailLoading: false,
    marketDetailError: null,
    orderReceipt: null,
    orderReceiptLoading: false,
    orderReceiptError: null,
  }),
  getters: {
    /** 热门竞猜：仅展示 open/closing 中的市场，避免展示锁定/结算态 */
    featuredMarkets: (s): PredictionMarket[] =>
      s.markets.filter((m) => m.market_status === 'open' || m.market_status === 'closing'),
    /** 广场「热门」分区：open 中 */
    openMarkets: (s): PredictionMarket[] =>
      s.markets.filter((m) => m.market_status === 'open'),
    /** 广场「即将截止」分区：closing 中 */
    closingMarkets: (s): PredictionMarket[] =>
      s.markets.filter((m) => m.market_status === 'closing'),
  },
  actions: {
    async fetchMarkets() {
      this.loading = true
      this.error = null
      try {
        const env = await predictionApi.markets()
        this.markets = env.data ?? []
        this.loaded = true
      } catch (e) {
        this.error = e instanceof Error ? e.message : '竞猜加载失败'
      } finally {
        this.loading = false
      }
    },
    async fetchMarketDetail(marketId: string) {
      this.marketDetailLoading = true
      this.marketDetailError = null
      try {
        const env = await predictionApi.marketDetail(marketId)
        this.marketDetail = env.data
      } catch (e) {
        this.marketDetailError = e instanceof Error ? e.message : '竞猜详情加载失败'
      } finally {
        this.marketDetailLoading = false
      }
    },
    async fetchOrderReceipt(orderId: string) {
      this.orderReceiptLoading = true
      this.orderReceiptError = null
      try {
        const env = await predictionApi.orderReceipt(orderId)
        this.orderReceipt = env.data
      } catch (e) {
        this.orderReceiptError = e instanceof Error ? e.message : '订单回执加载失败'
      } finally {
        this.orderReceiptLoading = false
      }
    },
    setMyOrders(orders: PredictionOrderBrief[]) {
      this.myOrders = orders
    },
    reset() {
      this.markets = []
      this.myOrders = []
      this.loaded = false
      this.loading = false
      this.error = null
      this.marketDetail = null
      this.marketDetailLoading = false
      this.marketDetailError = null
      this.orderReceipt = null
      this.orderReceiptLoading = false
      this.orderReceiptError = null
    },
  },
})
