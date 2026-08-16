/**
 * Power 领域客户端 + DTO —— 绑定 OpenAPI components/schemas/ledger.yaml#/PowerPosition（S02-P03）。
 * Power 是「可消耗、可恢复的操作资源」，不是手续费/收益。权威持仓只读，来自服务端。
 *
 * 契约缺口（RECORD 已登记）：
 * - Power Ledger（7 日变化 / 分笔消耗-释放明细）无冻结 DTO/路径
 *   （`/ai/users/{id}/computing-power-ledger` 有路径无 schema）→ M-POWER-001 不展示 7 日趋势，不伪造。
 * - PowerImpactPreview（Withdrawal / Robot Start / OTC Sell 的预计冻结）无端点
 *   → 前端不得自算 Power 影响，相关写操作在各自业务页 fail-closed。
 * - `power_cap_source_robot_level` 具体每级数值来自 Active Robot Rule / Parameter，前端不得计算，
 *   仅原样展示后端下发的 `limit` 与 `power_cap_source_robot_level`。
 */
import { get } from './http'
import type { Envelope } from './types'

/** ledger.yaml#/PowerPosition（scalar 字段，无状态机） */
export interface PowerPosition {
  user_id: string
  available: string
  frozen?: string
  consumed_period?: string
  released_period?: string
  recovering?: string
  limit?: string
  power_cap_source_robot_level?: number
  last_restore_at?: number
  next_restore_at?: number
  rule_version?: string
  object_version?: number
}

export const powerApi = {
  /** 我的 Power 持仓（operationId: me_power_position） */
  position: (): Promise<Envelope<PowerPosition>> =>
    get<PowerPosition>('/api/v1/me/power'),
}
