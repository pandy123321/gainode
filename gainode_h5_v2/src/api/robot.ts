/**
 * Robot 领域客户端 + DTO —— 绑定 OpenAPI components/schemas/robot.yaml（S02-P04）。
 *
 * 只暴露【只读端点】。三个写操作（启停 robot_action、升级 upgrade_orders、领取
 * reward_claims）后端 fail-closed（无 Active Release 时返回 503 DEPENDENCY_UNAVAILABLE），
 * 前端不得开放真实提交，故本文件不提供对应写方法（见 07 §S03-P02 步骤 5：按钮由
 * allowed_actions 驱动，不由本地状态推导）。
 *
 * 注意：`/ai/users/{id}/*` 取用户 id；C 端自取暂无 `me` 变体（契约缺口
 * S03-P02-HOME-ROBOT-ME-PATH），以 `/ai/users/me/*` 占位，后端冻结 `me` 语义后无需改调用层。
 */
import { get } from './http'
import type { Envelope } from './types'

/** robot.yaml#/RobotSummary（无 Active Release 时 source_status=UNAVAILABLE） */
export interface RobotSummary {
  robot_id: string
  level: number
  status: 'inactive' | 'active' | 'cooling' | 'review' | 'restricted' | 'paused'
  standard_capacity?: string
  rule_version?: string
}

/** robot.yaml#/RobotDetail —— 携带服务端下发 allowed_actions（前端只读） */
export interface RobotDetail {
  robot_id: string
  user_id: string
  level: number
  status: 'inactive' | 'active' | 'cooling' | 'review' | 'restricted' | 'paused'
  standard_capacity?: string
  capabilities?: string[]
  allowed_actions?: string[]
  rule_version?: string
  parameter_release_id?: string
  source_status?: 'AVAILABLE' | 'UNAVAILABLE'
  reason_code?: string
}

/** robot.yaml#/RobotRuleSnapshot —— 56 级规则读取器投影（无 Active Release 时 UNAVAILABLE） */
export interface RobotRuleSnapshot {
  source_status: 'AVAILABLE' | 'UNAVAILABLE'
  parameter_release_id?: string
  snapshot_id?: string
  rule_version?: string
  power_cap_by_level?: Record<string, string>
  upgrade_apt_requirement?: Record<string, unknown>
  ai_reward_budget_cap?: string
  ai_reward_period_cap?: string
  ai_reward_hold_period?: number
  ai_reward_expiry_period?: number
  ai_reward_claim_enabled?: boolean
  daily_yield_coefficient_source?: string
  daily_yield_coefficient_precision?: string
  reason_code?: string
}

export type AIRewardState =
  | 'candidate'
  | 'held'
  | 'pending_claim'
  | 'claiming'
  | 'claimed'
  | 'expired_returned'
  | 'review'
  | 'reversed'

/** robot.yaml#/AIReward —— 动态 Reward 记录（quantity_apt 为 decimal string，可为 0） */
export interface AIReward {
  reward_id: string
  user_id: string
  robot_id: string
  state: AIRewardState
  period?: string
  standard_capacity?: string
  daily_reward_coefficient?: string
  quantity_apt?: string
  eligibility_snapshot_id?: string
  budget_snapshot_id?: string
  claim_id?: string
  ledger_entry_id?: string
  expires_at?: number
}

export type RobotUpgradeOrderStatus =
  | 'pending'
  | 'processing'
  | 'completed'
  | 'failed'
  | 'cancelled'

/** robot.yaml#/RobotUpgradeOrder —— 升级订单（工作流对象，apt_cost 为 decimal string） */
export interface RobotUpgradeOrder {
  upgrade_order_id: string
  robot_id: string
  user_id: string
  from_level: number
  to_level: number
  status: RobotUpgradeOrderStatus
  apt_cost?: string
  power_cap_after?: string
  capacities_after?: string[]
  cooling_end_at?: number
  review_case_id?: string
  approval_id?: string
  ledger_entry_id?: string
  rule_version?: string
  parameter_release_id?: string
}

export const robotApi = {
  /** 用户 AI 汇总（operationId: robot_user_summary） */
  summary: (): Promise<Envelope<RobotSummary>> =>
    get<RobotSummary>('/api/v1/ai/users/me/summary'),

  /** 机器人详情（operationId: robot_detail，含 allowed_actions） */
  detail: (robotId: string): Promise<Envelope<RobotDetail>> =>
    get<RobotDetail>(`/api/v1/ai/robots/${robotId}`),

  /** 奖励列表（operationId: robot_rewards） */
  rewards: (): Promise<Envelope<AIReward[]>> =>
    get<AIReward[]>('/api/v1/ai/users/me/rewards'),

  /** 升级订单详情（operationId: robot_upgrade_order_detail，只读查询结果） */
  upgradeOrder: (upgradeOrderId: string): Promise<Envelope<RobotUpgradeOrder>> =>
    get<RobotUpgradeOrder>(`/api/v1/ai/users/me/upgrade-orders/${upgradeOrderId}`),
}
