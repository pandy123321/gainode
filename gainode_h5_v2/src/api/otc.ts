/**
 * OTC 领域客户端 + DTO —— 绑定 OpenAPI components/schemas/otc.yaml（S02-P06）。
 * OTC = Controlled Matching，禁止 K-Line / Order Book Trading Terminal / 红绿博彩感 / Guaranteed Fill / Guaranteed Redemption。
 * decimal 字段一律 string（quantity_apt / price / fee / Power）。
 *
 * 只暴露【只读端点】：order_book / order_detail / trades / user_orders。
 * 三个写操作（quote / order_create / order_cancel）后端 fail-closed（fee/limit/库存/Power 规则 TBC → 503
 * DEPENDENCY_UNAVAILABLE），前端不得开放真实提交，故本文件不提供对应写方法。
 *
 * 契约缺口（RECORD 已登记）：
 * - S03-P02-OTC-ELIGIBILITY：OtcEligibility schema 存在但无 C 端暴露路径（无 /me/otc-eligibility）→ M-OTC-001 不展示资格结论。
 * - S03-P02-OTC-CAPACITY：OtcCapacity schema 存在但无 /me/otc-capacity 路径，且 capacity/储备参数 TBC → null。
 * - S03-P02-OTC-ME-ORDERS：`/otc/users/{id}/orders` 取用户 id，C 端自取无 `me` 变体；以 `/otc/users/me/orders` 占位，
 *   后端冻结 `me` 语义后无需改调用层（与 S03-P02-HOME-ROBOT-ME-PATH 同口径）。
 * - S03-P02-OTC-QUOTE / -CREATE / -CANCEL：quote/order_create/order_cancel 写操作 fail-closed（503），不提供写方法。
 */
import { get } from './http'
import type { Envelope } from './types'

export type OtcOrderSide = 'BUY' | 'SELL'

/** otc.yaml#/OtcOrder.status 9 态（MC1 冻结） */
export type OtcOrderStatus =
  | 'draft'
  | 'review'
  | 'matching'
  | 'partial'
  | 'completed'
  | 'cancelled'
  | 'expired'
  | 'rejected'
  | 'disputed'

/** otc.yaml#/OtcOrder（decimal 字段一律 string） */
export interface OtcOrder {
  otc_order_id: string
  user_id: string
  side: OtcOrderSide
  status: OtcOrderStatus
  price?: string
  quantity_apt?: string
  filled_quantity_apt?: string
  remaining_quantity_apt?: string
  fee_apt?: string
  power_required?: string
  power_consumed?: string
  power_frozen?: string
  review_required?: number
  quote_id?: string
  snapshot_id?: string
  rule_version?: string
  parameter_release_id?: string
  policy_version?: string
  audit_event_id?: string
  object_version?: number
}

/** otc.yaml#/OtcTrade（单态 completed，append-only，Owner 2B1-ENUM-04） */
export interface OtcTrade {
  trade_id: string
  otc_order_id: string
  buyer_user_id: string
  seller_user_id: string
  status: 'completed'
  quantity_apt?: string
  price_apt?: string
  fee_apt?: string
  power_consumed?: string
  ledger_entry_ids?: string[]
  ledger_batch_id?: string
  audit_event_id?: string
  created_time?: number
}

export const otcApi = {
  /** 订单簿（operationId: otc_order_book，只读） */
  orderBook: (): Promise<Envelope<OtcOrder[]>> =>
    get<OtcOrder[]>('/api/v1/otc/order-book'),

  /** 订单详情（operationId: otc_order_detail，只读） */
  orderDetail: (id: string): Promise<Envelope<OtcOrder>> =>
    get<OtcOrder>(`/api/v1/otc/orders/${id}`),

  /** 成交列表（operationId: otc_trades，只读） */
  trades: (): Promise<Envelope<OtcTrade[]>> =>
    get<OtcTrade[]>('/api/v1/otc/trades'),

  /** 用户订单列表（operationId: otc_user_orders；C 端无 me 变体，`me` 占位） */
  myOrders: (): Promise<Envelope<OtcOrder[]>> =>
    get<OtcOrder[]>('/api/v1/otc/users/me/orders'),
}
