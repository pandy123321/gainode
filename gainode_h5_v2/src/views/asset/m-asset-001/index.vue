<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAssetStore } from '../../../stores/asset'
import { t } from '../../../i18n'
import FiveStateContainer from '../../../components/FiveStateContainer.vue'
import type { LedgerEntry } from '../../../api/asset'

const router = useRouter()
const asset = useAssetStore()

const balance = computed(() => asset.balance)

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

function reload() {
  asset.fetch()
  asset.fetchLedger()
}

onMounted(() => {
  if (!asset.loaded) asset.fetch()
  if (!asset.ledger.length) asset.fetchLedger()
})
</script>

<template>
  <main class="asset-root">
    <header class="page-header">
      <h1>{{ t('page.m_asset_001.title') }}</h1>
      <RouterLink to="/asset/ledger" class="header-link" data-testid="ledger-link">
        {{ t('page.m_asset_001.primary_action') }}
      </RouterLink>
    </header>

    <!-- APT Quantity Summary -->
    <FiveStateContainer
      :state="asset.loading ? 'loading' : asset.error ? 'error' : 'default'"
      :error-message="asset.error || ''"
      @retry="reload"
    >
      <section v-if="balance" class="hero" data-testid="asset-balance">
        <p class="hero-kicker">{{ t('page.m_asset_001.description') }}</p>
        <h2 class="hero-value">{{ balance.effective_available }}</h2>
        <p class="hero-unit">APT</p>
      </section>

      <section v-if="balance" class="card" data-testid="asset-breakdown">
        <div class="row">
          <span class="row-label">{{ t('page.m_asset_001.available') }}</span>
          <span class="row-value">{{ balance.effective_available }}</span>
        </div>
        <div class="row">
          <span class="row-label">{{ t('page.m_asset_001.frozen') }}</span>
          <span class="row-value">{{ balance.frozen_apt_i ?? '0' }}</span>
        </div>
        <div class="row">
          <span class="row-label">{{ t('page.m_asset_001.pending_hold') }}</span>
          <span class="row-value">{{ balance.aggregate_dispute_hold ?? '0' }}</span>
        </div>
        <div v-if="balance.total_earned_apt != null" class="row">
          <span class="row-label">{{ t('page.m_asset_001.total_earned') }}</span>
          <span class="row-value">{{ balance.total_earned_apt }}</span>
        </div>
        <div v-if="balance.total_spent_apt != null" class="row">
          <span class="row-label">{{ t('page.m_asset_001.total_spent') }}</span>
          <span class="row-value">{{ balance.total_spent_apt }}</span>
        </div>
      </section>
    </FiveStateContainer>

    <!-- OTC 快捷动作（H5-07 未实现 → fail-closed，不开放真实挂单） -->
    <section class="card" data-testid="asset-otc">
      <h3 class="card-title">{{ t('page.m_asset_001.otc_title') }}</h3>
      <div class="otc-grid">
        <button class="otc-btn" disabled data-testid="otc-buy">{{ t('page.m_asset_001.otc_buy') }}</button>
        <button class="otc-btn" disabled data-testid="otc-sell">{{ t('page.m_asset_001.otc_sell') }}</button>
      </div>
      <p class="hint">{{ t('page.m_asset_001.otc_restricted') }}</p>
    </section>

    <!-- Power 入口 -->
    <RouterLink to="/power" class="entry-card" data-testid="power-entry">
      <span class="entry-label">{{ t('page.m_asset_001.power_entry') }}</span>
      <span class="entry-arrow">›</span>
    </RouterLink>

    <!-- 最近流水 -->
    <section class="card" data-testid="asset-ledger-preview">
      <div class="card-head">
        <h3 class="card-title">{{ t('page.m_asset_001.recent_ledger') }}</h3>
        <RouterLink to="/asset/ledger" class="card-link">{{ t('page.m_asset_001.primary_action') }}</RouterLink>
      </div>
      <FiveStateContainer
        :state="asset.ledgerLoading ? 'loading' : asset.ledgerError ? 'error' : 'default'"
        :error-message="asset.ledgerError || ''"
        @retry="asset.fetchLedger"
      >
        <div v-if="asset.recentLedger.length" class="ledger-list">
          <div v-for="e in asset.recentLedger" :key="e.ledger_entry_id" class="ledger-row">
            <span class="ledger-main">
              <span class="ledger-type">{{ e.entry_type }}</span>
              <span class="ledger-state" :data-state="e.state">{{ stateLabel(e) }}</span>
            </span>
            <span class="ledger-qty" :data-dir="e.entry_direction">
              {{ directionLabel(e) }} {{ e.quantity }}
            </span>
          </div>
        </div>
        <p v-else class="empty" data-testid="ledger-empty">{{ t('page.m_asset_001.ledger_empty') }}</p>
      </FiveStateContainer>
    </section>

    <!-- 参考估值：无冻结来源，不展示（避免把估值当收入/兑付价） -->
    <section class="card" data-testid="asset-valuation">
      <p class="hint">{{ t('page.m_asset_001.valuation_not_shown') }}</p>
    </section>
  </main>
</template>

<style scoped>
.asset-root {
  max-width: 640px;
  margin: 0 auto;
  min-height: 100vh;
  background: var(--gray-50);
  color: var(--gray-950);
}
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--space-4);
  background: var(--white);
  border-bottom: var(--border-default);
}
.page-header h1 {
  margin: 0;
  font-size: 20px;
  font-weight: 800;
}
.header-link {
  font-size: 14px;
  color: var(--brand-blue-600);
  text-decoration: none;
}
.hero {
  padding: var(--space-6) var(--space-4);
  background: linear-gradient(180deg, var(--brand-blue-50), var(--white));
  text-align: center;
}
.hero-kicker {
  margin: 0 0 var(--space-2);
  color: var(--gray-500);
  font-size: 13px;
}
.hero-value {
  margin: 0;
  font-size: 32px;
  font-weight: 800;
}
.hero-unit {
  margin: var(--space-1) 0 0;
  font-size: 13px;
  font-weight: 700;
  color: var(--brand-blue-700);
  letter-spacing: 0.05em;
}
.card {
  margin: var(--space-3) var(--space-4) 0;
  padding: var(--space-4);
  background: var(--white);
  border: var(--border-default);
  border-radius: var(--radius-lg);
}
.card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--space-3);
}
.card-title {
  margin: 0 0 var(--space-3);
  font-size: 15px;
  font-weight: 700;
  color: var(--gray-800);
}
.card-head .card-title {
  margin: 0;
}
.card-link {
  font-size: 13px;
  color: var(--brand-blue-600);
  text-decoration: none;
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
}
.otc-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--space-3);
}
.otc-btn {
  height: 48px;
  border: none;
  border-radius: var(--radius-lg);
  background: var(--brand-blue-600);
  color: var(--white);
  font-size: 16px;
  font-weight: 700;
}
.otc-btn:disabled {
  background: var(--gray-200);
  color: var(--gray-500);
  cursor: not-allowed;
}
.hint {
  margin: var(--space-2) 0 0;
  color: var(--gray-400);
  font-size: 12px;
  text-align: center;
}
.entry-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: var(--space-3) var(--space-4) 0;
  padding: var(--space-4);
  min-height: 44px;
  background: var(--white);
  border: var(--border-default);
  border-radius: var(--radius-lg);
  text-decoration: none;
}
.entry-label {
  font-size: 15px;
  font-weight: 700;
  color: var(--gray-800);
}
.entry-arrow {
  font-size: 20px;
  color: var(--gray-400);
}
.ledger-list {
  display: flex;
  flex-direction: column;
}
.ledger-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 44px;
  border-bottom: 1px solid var(--gray-100);
}
.ledger-row:last-child {
  border-bottom: none;
}
.ledger-main {
  display: flex;
  align-items: center;
  gap: var(--space-2);
}
.ledger-type {
  font-size: 14px;
  color: var(--gray-800);
}
.ledger-state {
  padding: 1px 8px;
  font-size: 11px;
  border-radius: 999px;
  background: var(--gray-100);
  color: var(--gray-600);
}
.ledger-qty {
  font-size: 14px;
  font-weight: 700;
}
.ledger-qty[data-dir='1'] {
  color: var(--brand-blue-700);
}
.ledger-qty[data-dir='-1'] {
  color: var(--gray-600);
}
.empty {
  margin: var(--space-3) 0 0;
  color: var(--gray-400);
  font-size: 13px;
  text-align: center;
}
</style>
