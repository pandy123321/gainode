/**
 * Prediction 领域客户端 + DTO —— 绑定 OpenAPI components/schemas/prediction.yaml#/PredictionMarket（S02-P05）。
 * 首页仅读 FeaturedMarkets（热门竞猜），不在此完成下单/结算。
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

export const predictionApi = {
  /** 市场列表（operationId: prediction_markets） */
  markets: (): Promise<Envelope<PredictionMarket[]>> =>
    get<PredictionMarket[]>('/api/v1/markets'),
}
