<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { WalletApi } from '../../api/services'
import { useUserStore } from '../../stores/user'
import { t } from '../../i18n'

const router = useRouter()
const userStore = useUserStore()
const records = ref<any[]>([])
const totalIn = ref(0)
const totalOut = ref(0)
const loading = ref(true)
const page = ref(1)
const size = 20
const hasMore = ref(true)
const loadingMore = ref(false)
const listRef = ref<HTMLElement | null>(null)
let observer: IntersectionObserver | null = null

onMounted(async () => {
  // 累计收支从钱包数据读取
  userStore.loadFromStorage()
  if (userStore.state.wallets.length) {
    const first = userStore.state.wallets[0]
    if (first) {
      const funding = first['Funding'] || first || {}
      totalIn.value = parseFloat(funding['total_in']?.toString() || '0') || 0
      totalOut.value = parseFloat(funding['total_out']?.toString() || '0') || 0
    }
  }

  await loadRecords()
})

onUnmounted(() => {
  observer?.disconnect()
})

function setupObserver() {
  observer?.disconnect()
  nextTick(() => {
    const sentinel = listRef.value?.querySelector('.sentinel')
    if (!sentinel || !(sentinel instanceof HTMLElement)) return
    observer = new IntersectionObserver((entries) => {
      if (entries[0]?.isIntersecting && hasMore.value && !loadingMore.value && !loading.value) {
        loadMore()
      }
    }, { rootMargin: '100px' })
    observer.observe(sentinel)
  })
}

async function loadRecords() {
  loading.value = true
  page.value = 1
  hasMore.value = true
  const res = await WalletApi.getWalletLogs(1, size)
  if (res.code === 0 && res.data) {
    const data = res.data
    const list: any[] = Array.isArray(data) ? data : (data.data || [])
    records.value = list
    hasMore.value = list.length >= size
  }
  loading.value = false
  setupObserver()
}

async function loadMore() {
  if (loadingMore.value || !hasMore.value) return
  loadingMore.value = true
  page.value++
  const res = await WalletApi.getWalletLogs(page.value, size)
  if (res.code === 0 && res.data) {
    const data = res.data
    const list: any[] = Array.isArray(data) ? data : (data.data || [])
    records.value.push(...list)
    hasMore.value = list.length >= size
  }
  loadingMore.value = false
  setupObserver()
}

function directionLabel(r: any): string {
  const d = r.direction
  if (d === 1) return t('income')
  if (d === -1) return t('expense')
  return t('freeze_change')
}

function amountPrefix(r: any): string {
  const d = r.direction
  if (d === 1) return '+'
  if (d === -1) return '-'
  return ''
}

function amountColor(r: any): string {
  const d = r.direction
  if (d === 1) return 'positive'
  if (d === -1) return 'negative'
  return 'freeze'
}

function iconClass(r: any): string {
  const d = r.direction
  if (d === 1) return 'in'
  if (d === -1) return 'out'
  return 'freeze'
}

function displayBalance(r: any): number {
  const after = parseFloat(r.balance_after?.toString() || '0') || 0
  const frozen = parseFloat(r.frozen_after?.toString() || '0') || 0
  return after - frozen
}
</script>

<template>
  <div class="ledger-screen">
    <header class="screen-header">
      <button class="back-btn" @click="router.back()">
        <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
      <h1>{{ t('ledger_title') }}</h1>
    </header>

    <div class="ledger-content">
      <!-- Summary Cards -->
      <div class="summary-row">
        <div class="summary-card">
          <span>{{ t('total_in') }}</span>
          <strong class="in">+{{ totalIn.toFixed(2) }}</strong>
        </div>
        <div class="summary-card">
          <span>{{ t('total_out') }}</span>
          <strong class="out">-{{ totalOut.toFixed(2) }}</strong>
        </div>
      </div>

      <h3 class="section-title">{{ t('transaction_records') }}</h3>

      <div v-if="loading" class="loading-wrap"><div class="spinner"></div></div>
      <div v-else-if="!records.length" class="empty-text">{{ t('empty') }}</div>
      <div v-else class="record-list" ref="listRef">
        <div v-for="(r, i) in records" :key="i" class="record-item">
          <div class="item-icon" :class="iconClass(r)">
            <svg v-if="r.direction === 1" viewBox="0 0 24 24" fill="none" width="18" height="18"><path d="M12 5v14M18 13l-6 6-6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <svg v-else-if="r.direction === -1" viewBox="0 0 24 24" fill="none" width="18" height="18"><path d="M12 19V5M6 11l6-6 6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <svg v-else viewBox="0 0 24 24" fill="none" width="18" height="18"><circle cx="12" cy="12" r="8" stroke="currentColor" stroke-width="1.8"/><path d="M8 12h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          </div>
          <div class="item-info">
            <strong>{{ directionLabel(r) }} · {{ r.remark || r.wallet_type || '-' }}</strong>
            <span>{{ r.created_time || '' }}</span>
          </div>
          <div class="item-right">
            <strong :class="amountColor(r)">
              {{ amountPrefix(r) }}{{ (parseFloat(r.amount?.toString() || '0') || 0).toFixed(2) }}
            </strong>
            <span>{{ t('balance_display') }} {{ displayBalance(r).toFixed(2) }}</span>
          </div>
        </div>
        <div v-if="loadingMore" class="loading-more"><div class="spinner"></div></div>
        <div v-else-if="!hasMore && records.length > 10" class="no-more">{{ t('no_more') }}</div>
        <div class="sentinel"></div>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
$elevated: #0E1620;
$border: #1E2830;
$green: #3DDC97;
$pink: #FF6B9D;
$muted: #8A9CB0;

.ledger-screen { min-height: 100vh; background: #0A0E14; }

.screen-header {
  position: sticky; top: 0; z-index: 50;
  display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: rgba(14, 22, 32, 0.95); backdrop-filter: blur(10px);
  .back-btn { background: none; border: none; color: white; cursor: pointer; }
  h1 { font-size: 18px; font-weight: 700; color: white; margin: 0; }
}

.ledger-content { padding: 16px; }

.summary-row {
  display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px;

  .summary-card {
    padding: 12px; background: $elevated; border-radius: 12px; border: 1px solid $border;
    span { display: block; font-size: 12px; color: $muted; }
    strong { display: block; font-size: 24px; font-weight: 700; margin-top: 6px;
      &.in { color: $green; }
      &.out { color: $pink; }
    }
  }
}

.section-title { font-size: 16px; font-weight: 700; color: white; margin: 0 0 12px; }

.loading-wrap { display: flex; justify-content: center; padding: 40px;
  .spinner { width: 32px; height: 32px; border: 3px solid rgba($green, 0.2); border-top-color: $green; border-radius: 50%; animation: spin 0.8s linear infinite; }
}
.empty-text { text-align: center; padding: 40px 0; color: $muted; font-size: 14px; }

.loading-more { display: flex; justify-content: center; padding: 20px;
  .spinner { width: 24px; height: 24px; border: 2px solid rgba($green, 0.2); border-top-color: $green; border-radius: 50%; animation: spin 0.8s linear infinite; }
}
.no-more { text-align: center; padding: 20px; color: $muted; font-size: 12px; }
.sentinel { height: 1px; }

.record-list { display: grid; gap: 12px; }

.record-item {
  display: flex; align-items: center; gap: 12px; padding: 14px;
  background: $elevated; border-radius: 12px; border: 1px solid $border;

  .item-icon {
    width: 36px; height: 36px; display: grid; place-items: center;
    border-radius: 8px; flex-shrink: 0;
    &.in { background: rgba($green, 0.1); color: $green; }
    &.out { background: rgba($pink, 0.1); color: $pink; }
    &.freeze { background: rgba(#FFB800, 0.1); color: #FFB800; }
  }

  .item-info {
    flex: 1; min-width: 0;
    strong { display: block; font-size: 14px; color: white; font-weight: 600; }
    span { font-size: 11px; color: $muted; }
  }

  .item-right {
    text-align: right;
    strong { display: block; font-size: 15px; font-weight: 700; color: $pink;
      &.positive { color: $green; }
      &.freeze { color: #FFB800; }
    }
    span { font-size: 11px; color: $muted; }
  }
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
