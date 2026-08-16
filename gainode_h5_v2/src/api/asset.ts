/**
 * 资产领域客户端 + DTO —— 绑定 OpenAPI components/schemas/ledger.yaml#/AssetBalance（S02-P03）。
 * 权威余额只读，来自服务端，前端不本地推导。
 */
import { get } from './http'
import type { Envelope } from './types'

/** ledger.yaml#/AssetBalance（scalar 余额 + 聚合投影） */
export interface AssetBalance {
  account_id: string
  balance_apt_i: string
  balance_apt_c?: string
  frozen_apt_i?: string
  frozen_apt_c?: string
  total_earned_apt?: string
  total_spent_apt?: string
  aggregate_dispute_hold?: string
  effective_available: string
  rule_version?: string
  snapshot_id?: string
  object_version?: number
}

export const assetApi = {
  /** 我的 APT 余额（operationId: me_asset_balance） */
  balance: (): Promise<Envelope<AssetBalance>> =>
    get<AssetBalance>('/api/v1/me/asset'),
}
