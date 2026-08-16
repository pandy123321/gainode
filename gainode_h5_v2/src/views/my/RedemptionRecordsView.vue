<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { UserApi } from '../../api/services'
import { t } from '../../i18n'

const router = useRouter()
const records = ref<any[]>([])
const loading = ref(true)
const loadingMore = ref(false)
const page = ref(1)
const hasMore = ref(true)
const listRef = ref<HTMLElement | null>(null)
let observer: IntersectionObserver | null = null

onMounted(() => loadRecords())
onUnmounted(() => observer?.disconnect())

function setupObserver() {
  observer?.disconnect()
  nextTick(() => {
    const sentinel = listRef.value?.querySelector('.sentinel')
    if (!sentinel || !(sentinel instanceof HTMLElement)) return
    observer = new IntersectionObserver((entries) => {
      if (entries[0]?.isIntersecting && hasMore.value && !loadingMore.value) {
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
  const res = await UserApi.getMyPackets(page.value)
  if (res.code === 0 && res.data) {
    const data = res.data
    const list: any[] = Array.isArray(data) ? data : (data.data || [])
    records.value = list
    hasMore.value = (parseInt(data.total_page?.toString() || '0') || 0) > page.value
  }
  loading.value = false
  setupObserver()
}

async function loadMore() {
  if (!hasMore.value || loadingMore.value) return
  loadingMore.value = true
  page.value++
  const res = await UserApi.getMyPackets(page.value)
  if (res.code === 0 && res.data) {
    const data = res.data
    const list: any[] = Array.isArray(data) ? data : (data.data || [])
    records.value.push(...list)
    hasMore.value = (parseInt(data.total_page?.toString() || '0') || 0) > page.value
  }
  loadingMore.value = false
}
</script>

<template>
  <div class="records-screen">
    <header class="records-header">
      <button class="back-btn" @click="router.back()">
        <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
      <h1>{{ t('redemption_records') }}</h1>
    </header>

    <div class="records-content">
      <div v-if="loading" class="loading-wrap"><div class="spinner"></div></div>
      <div v-else-if="!records.length" class="empty-text">{{ t('empty') }}</div>
      <div v-else class="record-list" ref="listRef">
        <div v-for="(r, i) in records" :key="i" class="record-row">
          <div class="row-left">
            <strong>{{ r.item_no || '-' }}</strong>
            <span>{{ r.created_at || r.created_time || '-' }}</span>
          </div>
          <div class="row-right">
            <strong class="amount">{{ r.amount ? `+${r.amount} USDT` : '-' }}</strong>
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
$muted: #8A9CB0;

.records-screen { min-height: 100vh; background: #0A0E14; }

.records-header {
  position: sticky; top: 0; z-index: 50;
  display: flex; align-items: center; gap: 12px; padding: 12px 16px;
  background: rgba(14, 22, 32, 0.95); backdrop-filter: blur(10px);
  .back-btn { background: none; border: none; color: white; cursor: pointer; }
  h1 { font-size: 18px; font-weight: 700; color: white; margin: 0; }
}

.records-content { padding: 8px 16px 24px; }

.loading-wrap {
  display: flex; justify-content: center; padding: 40px;
  .spinner {
    width: 32px; height: 32px;
    border: 3px solid rgba($green, 0.2);
    border-top-color: $green;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }
}

.empty-text { text-align: center; padding: 80px 0; color: $muted; font-size: 14px; }

.record-list { display: grid; gap: 8px; }

.record-row {
  display: flex; justify-content: space-between; padding: 14px 16px;
  background: rgba(255,255,255,0.04); border-radius: 12px; border: 1px solid rgba(255,255,255,0.08);

  .row-left {
    strong { display: block; font-size: 14px; color: white; font-weight: 600; }
    span { font-size: 12px; color: $muted; }
  }
  .row-right {
    text-align: right;
    .amount { font-size: 15px; font-weight: 700; color: $green; }
  }
}

.loading-more {
  display: flex; justify-content: center; padding: 20px;
  .spinner {
    width: 24px; height: 24px;
    border: 2px solid rgba($green, 0.2);
    border-top-color: $green;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }
}

.no-more { text-align: center; padding: 16px; color: $muted; font-size: 12px; }

@keyframes spin { to { transform: rotate(360deg); } }
</style>
