import { defineStore } from 'pinia'

/** 资产/Power/OTC 状态 —— 权威余额只读，来自服务端，不持久化 */
export interface AssetSnapshot {
  aptAvailable: string
  aptFrozen: string
  powerAvailable: string
  powerFrozen: string
  powerCap: string
  sourceStatus: string
}

export const useAssetStore = defineStore('asset', {
  state: () => ({
    snapshot: null as AssetSnapshot | null,
    loaded: false,
  }),
  getters: {
    hasSnapshot: (s) => Boolean(s.snapshot),
  },
  actions: {
    setSnapshot(snapshot: AssetSnapshot) {
      this.snapshot = snapshot
      this.loaded = true
    },
    reset() {
      this.snapshot = null
      this.loaded = false
    },
  },
})
