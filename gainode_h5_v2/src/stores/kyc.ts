import { defineStore } from 'pinia'
import { kycApi, type KycCase, type KycStatus } from '../api/kyc'

interface KycState {
  kase: KycCase | null
  loaded: boolean
  loading: boolean
  error: string | null
}

export const useKycStore = defineStore('kyc', {
  state: (): KycState => ({
    kase: null,
    loaded: false,
    loading: false,
    error: null,
  }),
  getters: {
    status: (s): KycStatus | null => s.kase?.status ?? null,
    needsAction: (s): boolean => {
      const st = s.kase?.status
      return st === 'not_started' || st === 'needs_info'
    },
  },
  actions: {
    async fetch() {
      this.loading = true
      this.error = null
      try {
        const env = await kycApi.kycMe()
        this.kase = env.data
        this.loaded = true
      } catch (e) {
        this.error = e instanceof Error ? e.message : 'KYC 加载失败'
      } finally {
        this.loading = false
      }
    },
    reset() {
      this.kase = null
      this.loaded = false
      this.loading = false
      this.error = null
    },
  },
})
