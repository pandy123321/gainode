import { defineStore } from 'pinia'
import { eligibilityApi, type EligibilityResponse, type FeatureEntitlement } from '../api/kyc'

interface EntitlementState {
  response: EligibilityResponse | null
  loaded: boolean
  loading: boolean
  error: string | null
}

export const useEntitlementStore = defineStore('entitlement', {
  state: (): EntitlementState => ({
    response: null,
    loaded: false,
    loading: false,
    error: null,
  }),
  getters: {
    globalP: (s): FeatureEntitlement | null => s.response?.global_p ?? null,
    ai: (s): FeatureEntitlement | null => s.response?.ai ?? null,
    prediction: (s): FeatureEntitlement | null => s.response?.prediction ?? null,
  },
  actions: {
    async fetch() {
      this.loading = true
      this.error = null
      try {
        const env = await eligibilityApi.me()
        this.response = env.data
        this.loaded = true
      } catch (e) {
        this.error = e instanceof Error ? e.message : '资格加载失败'
      } finally {
        this.loading = false
      }
    },
    reset() {
      this.response = null
      this.loaded = false
      this.loading = false
      this.error = null
    },
  },
})
