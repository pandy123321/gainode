/**
 * Prediction 领域客户端 + DTO —— 绑定 OpenAPI components/schemas/prediction.yaml（S02-P05）。
 *
 * 只暴露【只读端点】：markets / market_detail / order_receipt。
 * 写操作（order_create / order_addition / appeal_create）后端 fail-closed
 * （依赖锁盘参数/资格/stake 或 RiskCase 契约未冻结 → 503 DEPENDENCY_UNAVAILABLE），
 * 前端不得开放真实提交（见 07 §S03-P02 步骤 5：按钮由 allowed_actions 驱动，不本地推导）。
 *
 * 契约缺口（RECORD 已登记）：
 * - market_disclosure 路径存在但无冻结 Disclosure DTO → 详情页规则/AI 参考不绑定，占位展示。
 * - `/me/prediction-orders`（我的竞猜列表）无路径 → M-PREDICT-004 保持 Restricted。
 * - `corrections/{id}`（更正详情）无路径 → M-PREDICT-006 保持 Restricted。
 * - PredictionOrder 无 settlement_id/result_id → 订单详情无法链到结算/赛果，M-PREDICT-005 仅订单回执。
 */
import { get } from './http'
import type { Envelope } from './types'

/** prediction.yaml#/PredictionMarket（9 态，MC1 冻结） */
export interface PredictionMarket {
  market_id: string
  event_id: string
  template_id: 'FOOTBALL_PREMATCH_1X2'
  market_status:
    | 'draft'
    | 'open'
    | 'closing'
    | 'locked'
    | 'awaiting_result'
    | 'settlement'
    | 'settled'
    | 'void'
    | 'exception'
  lock_at?: number
  selections?: string[]
  liquidity_summary?: string
  result_status?: 'provisional' | 'official' | 'disputed' | 'corrected'
  rule_version?: string
  parameter_release_id?: string
  policy_version?: string
  snapshot_id?: string
  object_version?: number
}

export type PredictionOrderStatus =
  | 'submitted'
  | 'locked'
  | 'awaiting_result'
  | 'settling'
  | 'settled'
  | 'refunding'
  | 'refunded'
  | 'correcting'
  | 'corrected'

/** prediction.yaml#/PredictionOrder（9 态，MC1 冻结，amount_apt 为 decimal string） */
export interface PredictionOrder {
  order_id: string
  user_id: string
  market_id: string
  selection: 'HOME' | 'DRAW' | 'AWAY'
  amount_apt: string
  order_status: PredictionOrderStatus
  asset_status?: string | null
  risk_status?: string | null
  consent_receipt_id?: string
  submit_snapshot_id?: string
  parameter_release_id?: string
  policy_version?: string
  audit_event_id?: string
  object_version?: number
}

export const predictionApi = {
  /** 市场列表（operationId: prediction_markets） */
  markets: (): Promise<Envelope<PredictionMarket[]>> =>
    get<PredictionMarket[]>('/api/v1/markets'),

  /** 市场详情（operationId: prediction_market_detail） */
  marketDetail: (marketId: string): Promise<Envelope<PredictionMarket>> =>
    get<PredictionMarket>(`/api/v1/markets/${marketId}`),

  /** 订单回执（operationId: prediction_order_receipt） */
  orderReceipt: (orderId: string): Promise<Envelope<PredictionOrder>> =>
    get<PredictionOrder>(`/api/v1/orders/${orderId}/receipt`),
}
