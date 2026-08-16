import { defineStore } from 'pinia'
import { assetApi, type AssetBalance } from '../api/asset'

interface AssetState {
  balance: AssetBalance | null
  loaded: boolean
  loading: boolean
  error: string | null
}

/** 资产/Power/OTC 状态 —— 权威余额只读，来自服务端，不持久化 */
export const useAssetStore = defineStore('asset', {
  state: (): AssetState => ({
    balance: null,
    loaded: false,
    loading: false,
    error: null,
  }),
  getters: {
    hasBalance: (s) => Boolean(s.balance),
  },
  actions: {
    async fetch() {
      this.loading = true
      this.error = null
      try {
        const env = await assetApi.balance()
        this.balance = env.data
        this.loaded = true
      } catch (e) {
        this.error = e instanceof Error ? e.message : '资产加载失败'
      } finally {
        this.loading = false
      }
    },
    reset() {
      this.balance = null
      this.loaded = false
      this.loading = false
      this.error = null
    },
  },
})
