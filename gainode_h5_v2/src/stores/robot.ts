import { defineStore } from 'pinia'
import { robotApi, type RobotSummary } from '../api/robot'

interface RobotState {
  summary: RobotSummary | null
  loaded: boolean
  loading: boolean
  error: string | null
}

export const useRobotStore = defineStore('robot', {
  state: (): RobotState => ({
    summary: null,
    loaded: false,
    loading: false,
    error: null,
  }),
  getters: {
    hasRobot: (s) => Boolean(s.summary?.robot_id),
  },
  actions: {
    async fetch() {
      this.loading = true
      this.error = null
      try {
        const env = await robotApi.summary()
        this.summary = env.data
        this.loaded = true
      } catch (e) {
        this.error = e instanceof Error ? e.message : 'Robot 加载失败'
      } finally {
        this.loading = false
      }
    },
    reset() {
      this.summary = null
      this.loaded = false
      this.loading = false
      this.error = null
    },
  },
})
