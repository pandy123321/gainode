/**
 * Robot 领域客户端 + DTO —— 绑定 OpenAPI components/schemas/robot.yaml#/RobotSummary（S02-P04）。
 *
 * 注意：路径 /ai/users/{id}/summary 取用户 id；C 端自取摘要尚无 `me` 变体（契约缺口
 * S03-P02-HOME-ROBOT-ME-PATH）。本文件以 /ai/users/me/summary 占位，后端冻结 `me` 语义后
 * 无需改调用层。
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

export const robotApi = {
  /** 用户 AI 汇总（operationId: robot_user_summary） */
  summary: (): Promise<Envelope<RobotSummary>> =>
    get<RobotSummary>('/api/v1/ai/users/me/summary'),
}
