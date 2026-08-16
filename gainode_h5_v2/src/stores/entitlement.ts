import { defineStore } from 'pinia'

export interface EntitlementState {
  loaded: boolean
  globalPLevel: number
  aiRewardEligible: boolean
  predictionEligible: boolean
  restrictedReason: string | null
}

export const useEntitlementStore = defineStore('entitlement', {
  state: (): EntitlementState => ({
    loaded: false,
    globalPLevel: 0,
    aiRewardEligible: false,
    predictionEligible: false,
    restrictedReason: null,
  }),
  getters: {
    anyEligible: (s) => s.aiRewardEligible || s.predictionEligible,
  },
  actions: {
    // S03-P02 接入 /me/entitlement；默认 deny（fail-closed）
    setLoaded(value: EntitlementState) {
      Object.assign(this, value, { loaded: true })
    },
    reset() {
      this.loaded = false
      this.globalPLevel = 0
      this.aiRewardEligible = false
      this.predictionEligible = false
      this.restrictedReason = null
    },
  },
})
