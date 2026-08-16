/**
 * 资产领域客户端 + DTO —— 绑定 OpenAPI components/schemas/ledger.yaml（S02-P03）。
 * 权威余额/流水只读，来自服务端，前端不本地推导。
 * 只暴露【只读端点】：asset_balance / ledger_entries。
 * 经济写路径（post/cancel/reverse/dispute）由内部 Authoritative Writer 触发，不对外暴露。
 *
 * 契约缺口（RECORD 已登记）：
 * - `/me/ledger-entries/{id}`（单笔流水详情）无路径 → M-ASSET-003 由列表已拉取的 entry 渲染，
 *   不发起额外详情请求（深链无数据时 Empty）。
 * - `Reference Valuation`（参考估值）无冻结来源端点 → M-ASSET-001 不展示，避免把估值当收入/兑付价。
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

/** ledger.yaml#/LedgerEntry（append-only 分录，quantity 恒正，方向由 entry_direction 表达） */
export interface LedgerEntry {
  ledger_entry_id: string
  account_id: string
  asset: 'APT-I'
  quantity: string
  entry_direction: 1 | -1
  entry_type: string
  state: 'pending' | 'posted' | 'reversed' | 'disputed'
  source_object_type?: string
  source_object_id?: string
  journal_batch_id?: string
  reversal_of?: string
  idempotency_key?: string | null
  rule_version?: string
  snapshot_id?: string
  audit_event_id?: string
  object_version?: number
  created_time?: number
}

export const assetApi = {
  /** 我的 APT 余额（operationId: me_asset_balance） */
  balance: (): Promise<Envelope<AssetBalance>> =>
    get<AssetBalance>('/api/v1/me/asset'),

  /** 我的账本分录列表（operationId: me_ledger_entries，按时间倒序） */
  ledgerEntries: (): Promise<Envelope<LedgerEntry[]>> =>
    get<LedgerEntry[]>('/api/v1/me/ledger-entries'),
}
