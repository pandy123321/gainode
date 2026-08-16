<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAssetStore } from '../../../stores/asset'
import { t } from '../../../i18n'
import FiveStateContainer from '../../../components/FiveStateContainer.vue'
import type { LedgerEntry } from '../../../api/asset'

const router = useRouter()
const asset = useAssetStore()

function directionLabel(e: LedgerEntry) {
  return e.entry_direction === 1 ? t('asset.direction.credit') : t('asset.direction.debit')
}
function stateLabel(e: LedgerEntry) {
  return t(`asset.ledger_state.${e.state}`)
}
function fmtTime(ts?: number) {
  if (ts == null) return ''
  return new Date(ts * 1000).toLocaleString()
}

onMounted(() => {
  if (!asset.ledger.length) asset.fetchLedger()
})
</script>

<template>
  <main class="ledger-list">
    <header class="page-header">
      <h1>{{ t('page.m_asset_002.title') }}</h1>
    </header>

    <FiveStateContainer
      :state="asset.ledgerLoading ? 'loading' : asset.ledgerError ? 'error' : 'default'"
      :error-message="asset.ledgerError || ''"
      @retry="asset.fetchLedger"
    >
      <div v-if="asset.ledger.length" class="list" data-testid="ledger-list">
        <button
          v-for="e in asset.ledger"
          :key="e.ledger_entry_id"
          class="ledger-row"
          data-testid="ledger-row"
          @click="router.push(`/asset/ledger/${e.ledger_entry_id}`)"
        >
          <span class="row-left">
            <span class="row-type">{{ e.entry_type }}</span>
            <span class="row-sub">
              <span class="row-state" :data-state="e.state">{{ stateLabel(e) }}</span>
              <span class="row-time">{{ fmtTime(e.created_time) }}</span>
            </span>
          </span>
          <span class="row-right">
            <span class="row-qty" :data-dir="e.entry_direction">{{ directionLabel(e) }}</span>
            <span class="row-amount">{{ e.quantity }}</span>
          </span>
        </button>
      </div>
      <p v-else class="empty" data-testid="ledger-empty">{{ t('page.m_asset_002.empty') }}</p>
    </FiveStateContainer>
  </main>
</template>

<style scoped>
.ledger-list {
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
.list {
  margin: var(--space-3) var(--space-4) 0;
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}
.ledger-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 68px;
  padding: var(--space-3) var(--space-4);
  text-align: left;
  background: var(--white);
  border: var(--border-default);
  border-radius: var(--radius-lg);
  cursor: pointer;
}
.row-left {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
}
.row-type {
  font-size: 14px;
  font-weight: 700;
  color: var(--gray-800);
}
.row-sub {
  display: flex;
  align-items: center;
  gap: var(--space-2);
}
.row-state {
  padding: 1px 8px;
  font-size: 11px;
  border-radius: 999px;
  background: var(--gray-100);
  color: var(--gray-600);
}
.row-state[data-state='reversed'] {
  background: var(--danger-50);
  color: var(--danger-700);
}
.row-state[data-state='disputed'] {
  background: var(--warning-50);
  color: var(--warning-700);
}
.row-time {
  font-size: 12px;
  color: var(--gray-400);
}
.row-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: var(--space-1);
}
.row-qty {
  font-size: 12px;
  color: var(--gray-500);
}
.row-amount {
  font-size: 16px;
  font-weight: 800;
}
.row-amount[data-dir='1'] {
  color: var(--brand-blue-700);
}
.row-amount[data-dir='-1'] {
  color: var(--gray-600);
}
.empty {
  margin: var(--space-6) 0 0;
  color: var(--gray-400);
  font-size: 13px;
  text-align: center;
}
</style>
