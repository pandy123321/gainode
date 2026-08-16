import { defineStore } from 'pinia'

export interface PredictionOrderBrief {
  orderId: string
  marketId: string
  status: string
}

export const usePredictionStore = defineStore('prediction', {
  state: () => ({
    markets: [] as unknown[],
    myOrders: [] as PredictionOrderBrief[],
    loaded: false,
  }),
  actions: {
    setMarkets(markets: unknown[]) {
      this.markets = markets
      this.loaded = true
    },
    setMyOrders(orders: PredictionOrderBrief[]) {
      this.myOrders = orders
    },
    reset() {
      this.markets = []
      this.myOrders = []
      this.loaded = false
    },
  },
})
