import { defineStore } from 'pinia'
import { powerApi, type PowerPosition } from '../api/power'

interface PowerState {
  position: PowerPosition | null
  loading: boolean
  error: string | null
}

/** Power 持仓 —— 权威只读，来自服务端，不持久化 */
export const usePowerStore = defineStore('power', {
  state: (): PowerState => ({
    position: null,
    loading: false,
    error: null,
  }),
  getters: {
    hasPosition: (s) => Boolean(s.position),
    /** 可用占比（用于 Battery 视觉，不参与任何业务计算） */
    ratio: (s): number | null => {
      const p = s.position
      if (!p) return null
      const avail = Number(p.available)
      const limit = Number(p.limit)
      if (!Number.isFinite(avail) || !Number.isFinite(limit) || limit <= 0) return null
      return Math.min(Math.max(avail / limit, 0), 1)
    },
  },
  actions: {
    async fetch() {
      this.loading = true
      this.error = null
      try {
        const env = await powerApi.position()
        this.position = env.data
      } catch (e) {
        this.error = e instanceof Error ? e.message : 'Power 加载失败'
      } finally {
        this.loading = false
      }
    },
    reset() {
      this.position = null
      this.loading = false
      this.error = null
    },
  },
})
