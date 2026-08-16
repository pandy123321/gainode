import { defineStore } from 'pinia'
import { predictionApi, type PredictionMarket } from '../api/prediction'

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
}

export const usePredictionStore = defineStore('prediction', {
  state: (): PredictionState => ({
    markets: [],
    myOrders: [],
    loaded: false,
    loading: false,
    error: null,
  }),
  getters: {
    /** 热门竞猜：仅展示 open/closing 中的市场，避免展示锁定/结算态 */
    featuredMarkets: (s): PredictionMarket[] =>
      s.markets.filter((m) => m.market_status === 'open' || m.market_status === 'closing'),
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
    setMyOrders(orders: PredictionOrderBrief[]) {
      this.myOrders = orders
    },
    reset() {
      this.markets = []
      this.myOrders = []
      this.loaded = false
      this.loading = false
      this.error = null
    },
  },
})
