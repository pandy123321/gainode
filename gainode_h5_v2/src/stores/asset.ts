import { defineStore } from 'pinia'
import { assetApi, type AssetBalance, type LedgerEntry } from '../api/asset'

interface AssetState {
  balance: AssetBalance | null
  ledger: LedgerEntry[]
  loaded: boolean
  loading: boolean
  error: string | null
  ledgerLoading: boolean
  ledgerError: string | null
}

/** 资产/Power/OTC 状态 —— 权威余额/流水只读，来自服务端，不持久化 */
export const useAssetStore = defineStore('asset', {
  state: (): AssetState => ({
    balance: null,
    ledger: [],
    loaded: false,
    loading: false,
    error: null,
    ledgerLoading: false,
    ledgerError: null,
  }),
  getters: {
    hasBalance: (s) => Boolean(s.balance),
    /** 最近流水预览（首页/资产页最多 5 条） */
    recentLedger: (s): LedgerEntry[] => s.ledger.slice(0, 5),
    /** 按 id 查单笔流水（供 M-ASSET-003 详情；无详情端点，从已拉取列表取） */
    entryById: (s) => (id: string) => s.ledger.find((e) => e.ledger_entry_id === id) ?? null,
  },
  actions: {
    async fetch() {
      this.loading = true
      this.error = null
      try {
        const env = await assetApi.balance()
        this.balance = env.data
        this.loaded = true
      } catch (e) {
        this.error = e instanceof Error ? e.message : '资产加载失败'
      } finally {
        this.loading = false
      }
    },
    async fetchLedger() {
      this.ledgerLoading = true
      this.ledgerError = null
      try {
        const env = await assetApi.ledgerEntries()
        this.ledger = env.data ?? []
      } catch (e) {
        this.ledgerError = e instanceof Error ? e.message : '流水加载失败'
      } finally {
        this.ledgerLoading = false
      }
    },
    reset() {
      this.balance = null
      this.ledger = []
      this.loaded = false
      this.loading = false
      this.error = null
      this.ledgerLoading = false
      this.ledgerError = null
    },
  },
})
