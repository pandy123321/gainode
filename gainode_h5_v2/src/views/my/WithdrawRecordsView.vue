<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { WalletApi } from '../../api/services'
import { t } from '../../i18n'

const router = useRouter()
const records = ref<any[]>([])
const loading = ref(true)

onMounted(async () => {
  const res = await WalletApi.getWithdrawRecords()
  if (res.code === 0 && res.data) {
    const data = res.data
    records.value = Array.isArray(data) ? data : (data.data || [])
  }
  loading.value = false
})

function statusText(status: string): string {
  const map: Record<string, string> = {
    requested: t('withdraw_status_requested'),
    approved: t('withdraw_status_approved'),
    rejected: t('withdraw_status_rejected'),
  }
  return map[status] || status || ''
}

function statusClass(status: string): string {
  return status || ''
}
</script>

<template>
  <div class="records-screen">
    <header class="screen-header">
      <button class="back-btn" @click="router.back()">
        <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
      <h1>{{ t('withdraw_records') }}</h1>
    </header>
    <div class="records-content">
      <div v-if="loading" class="loading-wrap"><div class="spinner"></div></div>
      <div v-else-if="!records.length" class="empty-text">{{ t('empty') }}</div>
      <div v-else class="record-list">
        <div v-for="(r, i) in records" :key="i" class="record-item">
          <div class="item-left">
            <strong>{{ r.network_name || 'Withdraw' }}</strong>
            <span>{{ r.created_time || '' }}</span>
          </div>
          <div class="item-right">
            <strong>-{{ parseFloat(r.money || '0').toFixed(2) }} USDT</strong>
            <span class="status" :class="statusClass(r.order_status)">{{ statusText(r.order_status) }}</span>
          </div>
        </div>
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

.records-screen { min-height: 100vh; background: #0A0E14; }
.screen-header {
  position: sticky; top: 0; z-index: 50;
  display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: rgba(14, 22, 32, 0.95); backdrop-filter: blur(10px);
  .back-btn { background: none; border: none; color: white; cursor: pointer; }
  h1 { font-size: 18px; font-weight: 700; color: white; margin: 0; }
}
.records-content { padding: 16px; }
.loading-wrap { display: flex; justify-content: center; padding: 40px;
  .spinner { width: 32px; height: 32px; border: 3px solid rgba($green, 0.2); border-top-color: $green; border-radius: 50%; animation: spin 0.8s linear infinite; }
}
.empty-text { text-align: center; padding: 80px 0; color: $muted; font-size: 14px; }
.record-list { display: grid; gap: 8px; }
.record-item {
  display: flex; justify-content: space-between; align-items: center; padding: 14px 16px;
  background: $elevated; border-radius: 12px; border: 1px solid $border;
  .item-left {
    strong { display: block; font-size: 14px; color: white; }
    span { font-size: 12px; color: $muted; }
  }
  .item-right {
    text-align: right;
    strong { display: block; font-size: 15px; font-weight: 700; color: $pink; }
    .status {
      font-size: 11px;
      &.requested { color: #FFB800; }
      &.approved { color: $green; }
      &.rejected { color: $pink; }
    }
  }
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
