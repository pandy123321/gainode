<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { ProjectApi } from '../../api/services'
import { t } from '../../i18n'

const router = useRouter()
const signals = ref<any[]>([])
const totalCount = ref(0)
const formattedTotalCount = computed(() => totalCount.value.toLocaleString())
const maxEv = ref('0%')
const loading = ref(true)
const loadingMore = ref(false)
const page = ref(1)
const hasMore = ref(true)
const listRef = ref<HTMLElement | null>(null)
let observer: IntersectionObserver | null = null

onMounted(() => loadSignals())
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

async function loadSignals() {
  loading.value = true
  page.value = 1
  hasMore.value = true
  const res = await ProjectApi.getSignalList(page.value)
  if (res.code === 0 && res.data) {
    const data = res.data
    totalCount.value = parseInt(data.count?.toString() || '0') || 0
    const list: any[] = data.data || data || []
    let maxRate = 0
    signals.value = list.map((e: any) => {
      const rate = parseFloat(e.profit_rate?.toString() || '0') || 0
      if (rate > maxRate) maxRate = rate
      return e
    })
    maxEv.value = `${(maxRate * 100).toFixed(2)}%`
    hasMore.value = (parseInt(data.total_page?.toString() || '0') || 0) > page.value
  }
  loading.value = false
  setupObserver()
}

async function loadMore() {
  if (loadingMore.value || !hasMore.value) return
  loadingMore.value = true
  page.value++
  const res = await ProjectApi.getSignalList(page.value)
  if (res.code === 0 && res.data) {
    const data = res.data
    const list: any[] = data.data || data || []
    signals.value.push(...list)
    hasMore.value = (parseInt(data.total_page?.toString() || '0') || 0) > page.value
  }
  loadingMore.value = false
  setupObserver()
}

function timeAgo(ts: any): string {
  if (!ts) return ''
  const s = parseInt(ts.toString()) || 0
  if (!s) return ''
  const diff = Math.floor(Date.now() / 1000) - s
  if (diff < 60) return `${diff}s`
  if (diff < 3600) return `${Math.floor(diff / 60)}m`
  return `${Math.floor(diff / 3600)}h`
}

function evPercent(rate: any): string {
  return `${((parseFloat(rate?.toString() || '0') || 0) * 100).toFixed(2)}%`
}
</script>

<template>
  <div class="signals-screen">
    <header class="signals-header">
      <button class="back-btn" @click="router.back()">
        <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
      <div>
        <h1>{{ t('scanning') }}</h1>
        <p>{{ t('realtime_monitoring_ev') }}</p>
      </div>
    </header>

    <div class="stats-row">
      <div class="stat-card">
        <strong>{{ formattedTotalCount }}</strong>
        <span>{{ t('all_opportunities') }}</span>
      </div>
      <div class="stat-card">
        <strong>{{ maxEv }}</strong>
        <span>{{ t('max_ev') }}</span>
      </div>
    </div>

    <div v-if="loading" class="loading-wrap"><div class="spinner"></div></div>

    <div v-else class="signal-list" ref="listRef">
      <div v-for="(s, i) in signals" :key="i" class="signal-card">
        <div class="signal-top">
          <span class="sport-tag">⚽ {{ t('football') }}</span>
          <span class="time-tag">{{ timeAgo(s.last_seen_at) }}</span>
          <strong class="ev-value">{{ evPercent(s.profit_rate) }}</strong>
        </div>
        <h4>{{ s.event_name }}</h4>
        <div class="bet-row" v-if="s.leg1_bookmaker">
          <span class="bookmaker">{{ s.leg1_bookmaker }} · {{ s.leg1_market }}</span>
          <span class="odds">{{ s.leg1_odds }}</span>
        </div>
        <div class="bet-row" v-if="s.leg2_bookmaker">
          <span class="bookmaker">{{ s.leg2_bookmaker }} · {{ s.leg2_market }}</span>
          <span class="odds">{{ s.leg2_odds }}</span>
        </div>
      </div>
      <div v-if="loadingMore" class="loading-more"><div class="spinner"></div></div>
      <div v-else-if="!hasMore && signals.length > 10" class="no-more">{{ t('no_more') }}</div>
      <div class="sentinel"></div>
    </div>
  </div>
</template>

<style scoped lang="scss">
$elevated: #0E1620;
$border: #1E2830;
$green: #3DDC97;
$muted: #8A9CB0;

.signals-screen {
  min-height: 100vh;
  background: #0A0E14;
}

.signals-header {
  position: sticky;
  top: 0;
  z-index: 50;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: rgba(14, 22, 32, 0.95);
  backdrop-filter: blur(10px);

  .back-btn { background: none; border: none; color: white; cursor: pointer; }
  h1 { font-size: 18px; font-weight: 700; color: white; margin: 0; }
  p { font-size: 12px; color: $muted; margin: 2px 0 0; }
}

.stats-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  padding: 16px;

  .stat-card {
    padding: 10px;
    background: $elevated;
    border-radius: 8px;
    border: 1px solid $border;

    strong { display: block; font-size: 22px; font-weight: 700; color: $green; }
    span { font-size: 12px; color: $muted; }
  }
}

.loading-wrap {
  display: flex;
  justify-content: center;
  padding: 40px;

  .spinner {
    width: 32px; height: 32px;
    border: 3px solid rgba($green, 0.2);
    border-top-color: $green;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
  }
}

.signal-list {
  padding: 0 16px 24px;
  display: grid;
  gap: 12px;
}

.signal-card {
  padding: 18px;
  background: $elevated;
  border-radius: 14px;
  border: 1px solid $border;

  .signal-top {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px;
  }

  .sport-tag, .time-tag {
    padding: 5px 10px;
    background: $border;
    border-radius: 6px;
    font-size: 12px;
    color: white;
  }

  .time-tag { color: $muted; }

  .ev-value {
    margin-left: auto;
    font-size: 18px;
    font-weight: 700;
    color: $green;
  }

  h4 {
    font-size: 14px;
    font-weight: 700;
    color: white;
    margin: 0 0 16px;
  }

  .bet-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;

    .bookmaker { font-size: 13px; color: $muted; }
    .odds {
      padding: 2px 8px;
      border: 1.5px solid $green;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 700;
      color: $green;
    }
  }
}

.loading-more { display: flex; justify-content: center; padding: 20px;
  .spinner { width: 24px; height: 24px; border: 2px solid rgba($green, 0.2); border-top-color: $green; border-radius: 50%; animation: spin 0.8s linear infinite; }
}
.no-more { text-align: center; padding: 20px; color: $muted; font-size: 12px; }
.sentinel { height: 1px; }

@keyframes spin { to { transform: rotate(360deg); } }
</style>
