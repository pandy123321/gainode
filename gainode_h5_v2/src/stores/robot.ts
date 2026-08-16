import { defineStore } from 'pinia'

export interface RobotSummary {
  robotId: string | null
  level: number
  status: string | null
  claimableReward: string | null
}

export const useRobotStore = defineStore('robot', {
  state: () => ({
    summary: null as RobotSummary | null,
    loaded: false,
  }),
  getters: {
    hasRobot: (s) => Boolean(s.summary?.robotId),
  },
  actions: {
    setSummary(summary: RobotSummary | null) {
      this.summary = summary
      this.loaded = true
    },
    reset() {
      this.summary = null
      this.loaded = false
    },
  },
})
