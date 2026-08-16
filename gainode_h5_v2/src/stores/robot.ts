import { defineStore } from 'pinia'
import {
  robotApi,
  type AIReward,
  type RobotDetail,
  type RobotSummary,
  type RobotUpgradeOrder,
} from '../api/robot'

interface RobotState {
  summary: RobotSummary | null
  loaded: boolean
  loading: boolean
  error: string | null
  detail: RobotDetail | null
  detailLoading: boolean
  detailError: string | null
  rewards: AIReward[]
  rewardsLoading: boolean
  rewardsError: string | null
  upgradeOrder: RobotUpgradeOrder | null
  upgradeOrderLoading: boolean
  upgradeOrderError: string | null
}

export const useRobotStore = defineStore('robot', {
  state: (): RobotState => ({
    summary: null,
    loaded: false,
    loading: false,
    error: null,
    detail: null,
    detailLoading: false,
    detailError: null,
    rewards: [],
    rewardsLoading: false,
    rewardsError: null,
    upgradeOrder: null,
    upgradeOrderLoading: false,
    upgradeOrderError: null,
  }),
  getters: {
    hasRobot: (s) => Boolean(s.summary?.robot_id),
    /** 服务端下发允许动作（前端只读，不本地推导） */
    allowedActions: (s): string[] => s.detail?.allowed_actions ?? [],
    /** 依赖不可用（无 Active Release）→ 页面整体 Restricted */
    sourceUnavailable: (s): boolean =>
      s.detail != null && s.detail.source_status === 'UNAVAILABLE',
    /** 可领取奖励（held/pending_claim），用于 Claim 入口（但 Claim 提交仍 fail-closed） */
    claimableRewards: (s): AIReward[] =>
      s.rewards.filter((r) => r.state === 'held' || r.state === 'pending_claim'),
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
    async fetchDetail(robotId: string) {
      this.detailLoading = true
      this.detailError = null
      try {
        const env = await robotApi.detail(robotId)
        this.detail = env.data
      } catch (e) {
        this.detailError = e instanceof Error ? e.message : 'Robot 详情加载失败'
      } finally {
        this.detailLoading = false
      }
    },
    async fetchRewards() {
      this.rewardsLoading = true
      this.rewardsError = null
      try {
        const env = await robotApi.rewards()
        this.rewards = env.data ?? []
      } catch (e) {
        this.rewardsError = e instanceof Error ? e.message : 'Reward 加载失败'
      } finally {
        this.rewardsLoading = false
      }
    },
    async fetchUpgradeOrder(upgradeOrderId: string) {
      this.upgradeOrderLoading = true
      this.upgradeOrderError = null
      try {
        const env = await robotApi.upgradeOrder(upgradeOrderId)
        this.upgradeOrder = env.data
      } catch (e) {
        this.upgradeOrderError = e instanceof Error ? e.message : '升级订单加载失败'
      } finally {
        this.upgradeOrderLoading = false
      }
    },
    reset() {
      this.summary = null
      this.loaded = false
      this.loading = false
      this.error = null
      this.detail = null
      this.detailLoading = false
      this.detailError = null
      this.rewards = []
      this.rewardsLoading = false
      this.rewardsError = null
      this.upgradeOrder = null
      this.upgradeOrderLoading = false
      this.upgradeOrderError = null
    },
  },
})
