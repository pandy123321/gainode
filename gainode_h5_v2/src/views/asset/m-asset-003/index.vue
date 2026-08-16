<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAssetStore } from '../../../stores/asset'
import { t } from '../../../i18n'
import FiveStateContainer from '../../../components/FiveStateContainer.vue'
import type { LedgerEntry } from '../../../api/asset'

const route = useRoute()
const router = useRouter()
const asset = useAssetStore()

const entryId = computed(() => String(route.params.id ?? ''))
const entry = computed<LedgerEntry | null>(() => asset.entryById(entryId.value))

function directionLabel(e: LedgerEntry) {
  return e.entry_direction === 1 ? t('asset.direction.credit') : t('asset.direction.debit')
}
function stateLabel(e: LedgerEntry) {
  return t(`asset.ledger_state.${e.state}`)
}
function fmtTime(ts?: number) {
  if (ts == null) return '-'
  return new Date(ts * 1000).toLocaleString()
}

onMounted(() => {
  // 无详情端点：从已拉取列表取；若未加载（深链）则拉列表一次
  if (!asset.ledger.length && !asset.ledgerLoading) asset.fetchLedger()
})
</script>

<template>
  <main class="ledger-detail">
    <header class="page-header">
      <h1>{{ t('page.m_asset_003.title') }}</h1>
    </header>

    <FiveStateContainer
      :state="asset.ledgerLoading ? 'loading' : 'default'"
      :error-message="asset.ledgerError || ''"
      @retry="asset.fetchLedger"
    >
      <section v-if="entry" class="result" data-testid="ledger-detail">
        <div class="result-hero" :data-dir="entry.entry_direction">
          <p class="result-kicker">{{ t('page.m_asset_003.description') }}</p>
          <h2 class="result-status">{{ directionLabel(entry) }} {{ entry.quantity }}</h2>
          <p class="result-state">{{ stateLabel(entry) }}</p>
        </div>

        <div class="card">
          <div class="row">
            <span class="row-label">{{ t('page.m_asset_003.entry_id') }}</span>
            <span class="row-value mono">{{ entry.ledger_entry_id }}</span>
          </div>
          <div class="row">
            <span class="row-label">{{ t('page.m_asset_003.entry_type') }}</span>
            <span class="row-value">{{ entry.entry_type }}</span>
          </div>
          <div class="row">
            <span class="row-label">{{ t('page.m_asset_003.direction') }}</span>
            <span class="row-value">{{ directionLabel(entry) }}</span>
          </div>
          <div class="row">
            <span class="row-label">{{ t('page.m_asset_003.state') }}</span>
            <span class="row-value">{{ stateLabel(entry) }}</span>
          </div>
          <div v-if="entry.source_object_type || entry.source_object_id" class="row">
            <span class="row-label">{{ t('page.m_asset_003.source') }}</span>
            <span class="row-value mono">{{ entry.source_object_type }} / {{ entry.source_object_id }}</span>
          </div>
          <div v-if="entry.journal_batch_id" class="row">
            <span class="row-label">{{ t('page.m_asset_003.journal_batch') }}</span>
            <span class="row-value mono">{{ entry.journal_batch_id }}</span>
          </div>
          <div class="row">
            <span class="row-label">{{ t('page.m_asset_003.created_time') }}</span>
            <span class="row-value">{{ fmtTime(entry.created_time) }}</span>
          </div>
        </div>

        <!-- 冲正：reversal_of 关联原分录，展示引用；反向分录本身在列表中可见 -->
        <div v-if="entry.reversal_of" class="card" data-testid="reversal">
          <h3 class="card-title">{{ t('page.m_asset_003.reversal_of') }}</h3>
          <span class="row-value mono">{{ entry.reversal_of }}</span>
        </div>

        <div v-if="entry.rule_version || entry.snapshot_id" class="card">
          <div v-if="entry.rule_version" class="row">
            <span class="row-label">{{ t('page.m_asset_003.rule_version') }}</span>
            <span class="row-value mono">{{ entry.rule_version }}</span>
          </div>
          <div v-if="entry.snapshot_id" class="row">
            <span class="row-label">{{ t('page.m_asset_003.snapshot_id') }}</span>
            <span class="row-value mono">{{ entry.snapshot_id }}</span>
          </div>
        </div>
      </section>

      <section v-else-if="!asset.ledgerLoading" class="card" data-testid="ledger-not-found">
        <p class="empty">{{ t('page.m_asset_003.not_found') }}</p>
      </section>
    </FiveStateContainer>

    <div class="actions">
      <button class="btn-primary" data-testid="back" @click="router.push('/asset/ledger')">
        {{ t('page.m_asset_003.primary_action') }}
      </button>
    </div>
  </main>
</template>

<style scoped>
.ledger-detail {
  max-width: 640px;
  margin: 0 auto;
  min-height: 100vh;
  background: var(--gray-50);
  color: var(--gray-950);
}
.page-header {
  padding: var(--space-4);
  background: var(--white);
  border-bottom: var(--border-default);
}
.page-header h1 {
  margin: 0;
  font-size: 20px;
  font-weight: 800;
}
.result-hero {
  padding: var(--space-6) var(--space-4);
  background: linear-gradient(180deg, var(--brand-blue-50), var(--white));
}
.result-kicker {
  margin: 0 0 var(--space-2);
  color: var(--gray-500);
  font-size: 13px;
}
.result-status {
  margin: 0 0 var(--space-2);
  font-size: 24px;
  font-weight: 800;
}
.result-state {
  margin: 0;
  font-size: 13px;
  color: var(--gray-500);
}
.card {
  margin: var(--space-3) var(--space-4) 0;
  padding: var(--space-4);
  background: var(--white);
  border: var(--border-default);
  border-radius: var(--radius-lg);
}
.card-title {
  margin: 0 0 var(--space-2);
  font-size: 15px;
  font-weight: 700;
  color: var(--gray-800);
}
.row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  min-height: 44px;
  border-bottom: 1px solid var(--gray-100);
}
.row:last-child {
  border-bottom: none;
}
.row-label {
  color: var(--gray-500);
  font-size: 14px;
}
.row-value {
  font-weight: 700;
  font-size: 14px;
  text-align: right;
  word-break: break-all;
}
.row-value.mono {
  font-family: var(--font-mono, monospace);
  font-size: 12px;
}
.empty {
  margin: 0;
  color: var(--gray-400);
  font-size: 13px;
  text-align: center;
}
.actions {
  margin: var(--space-4);
}
.btn-primary {
  width: 100%;
  height: 48px;
  border: none;
  border-radius: var(--radius-lg);
  background: var(--brand-blue-600);
  color: var(--white);
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
}
</style>
