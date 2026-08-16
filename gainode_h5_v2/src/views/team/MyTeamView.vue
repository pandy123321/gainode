<script setup lang="ts">
import { ref, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { TeamApi } from '../../api/services'
import { t } from '../../i18n'

const router = useRouter()
const members = ref<any[]>([])
const loading = ref(true)
const selectedTab = ref(0)
const page = ref(1)
const size = 20
const hasMore = ref(true)
const loadingMore = ref(false)
const listRef = ref<HTMLElement | null>(null)
let observer: IntersectionObserver | null = null

onMounted(() => {
  loadMembers()
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

async function loadMembers(showLoading = true) {
  if (showLoading) loading.value = true
  page.value = 1
  hasMore.value = true
  const res = await TeamApi.getTeamList({ type: selectedTab.value + 1, page: 1, size })
  if (res.code === 0 && res.data) {
    const data = res.data
    const list: any[] = Array.isArray(data) ? data : (data.data || [])
    members.value = list
    hasMore.value = list.length >= size
  }
  loading.value = false
  setupObserver()
}

async function loadMore() {
  if (loadingMore.value || !hasMore.value) return
  loadingMore.value = true
  page.value++
  const res = await TeamApi.getTeamList({ type: selectedTab.value + 1, page: page.value, size })
  if (res.code === 0 && res.data) {
    const data = res.data
    const list: any[] = Array.isArray(data) ? data : (data.data || [])
    members.value.push(...list)
    hasMore.value = list.length >= size
  }
  loadingMore.value = false
  setupObserver()
}

function switchTab(idx: number) {
  selectedTab.value = idx
  loadMembers(false)
}
</script>

<template>
  <div class="my-team-screen">
    <header class="team-header">
      <button class="back-btn" @click="router.back()">
        <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
      <h1>{{ t('my_team_title') }}</h1>
    </header>

    <div class="team-content">
      <div class="tab-row">
        <button
          class="tab-btn"
          :class="{ active: selectedTab === 0 }"
          @click="switchTab(0)"
        >{{ t('tab_direct_referral') }}</button>
        <button
          class="tab-btn"
          :class="{ active: selectedTab === 1 }"
          @click="switchTab(1)"
        >{{ t('tab_indirect_referral') }}</button>
        <span class="count-text">{{ t('total_people_count', { n: String(members.length) }) }}</span>
      </div>

      <div v-if="loading" class="loading-wrap"><div class="spinner"></div></div>
      <div v-else-if="!members.length" class="empty-text">{{ t('empty') }}</div>
      <div v-else class="member-list" ref="listRef">
        <div v-for="(m, i) in members" :key="i" class="member-card">
          <div class="member-top">
            <div class="avatar-circle">
              <svg viewBox="0 0 24 24" fill="none" width="22" height="22"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.8"/><path d="M4 20c0-3.9 3.6-7 8-7s8 3.1 8 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
            </div>
            <div class="member-info">
              <strong>{{ m.account || '-' }}</strong>
              <span>UID: {{ m.user_no || '-' }}</span>
            </div>
            <span class="member-date">{{ m.created_time || '' }}</span>
          </div>
          <div class="member-stats">
            <div class="stat-item">
              <span>{{ t('direct_referral_count') }}</span>
              <strong>{{ m.invite_cnt || 0 }}</strong>
            </div>
            <div class="stat-item">
              <span>{{ t('team_member_count') }}</span>
              <strong>{{ m.team_cnt || 0 }}</strong>
            </div>
            <div class="stat-item">
              <span>{{ t('team_earnings_label') }}</span>
              <strong>{{ (parseFloat(m.team_income_money?.toString() || '0') || 0).toFixed(2) }}</strong>
            </div>
          </div>
        </div>
        <div v-if="loadingMore" class="loading-more"><div class="spinner"></div></div>
        <div v-else-if="!hasMore && members.length > 10" class="no-more">{{ t('no_more') }}</div>
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

.my-team-screen { min-height: 100vh; background: #0A0E14; }

.team-header {
  position: sticky; top: 0; z-index: 50;
  display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: rgba(14, 22, 32, 0.95); backdrop-filter: blur(10px);
  .back-btn { background: none; border: none; color: white; cursor: pointer; }
  h1 { font-size: 18px; font-weight: 700; color: white; margin: 0; }
}

.team-content { padding: 16px; }

.tab-row {
  display: flex; align-items: center; gap: 12px; margin-bottom: 16px;

  .tab-btn {
    padding: 6px 0; background: none; border: none;
    color: $muted; font-size: 15px; cursor: pointer;
    &.active { color: $green; font-weight: 700; }
  }

  .count-text { font-size: 12px; color: $muted; margin-left: auto; }
}

.loading-wrap { display: flex; justify-content: center; padding: 40px;
  .spinner { width: 32px; height: 32px; border: 3px solid rgba($green, 0.2); border-top-color: $green; border-radius: 50%; animation: spin 0.8s linear infinite; }
}
.empty-text { text-align: center; padding: 40px; color: $muted; font-size: 14px; }

.loading-more { display: flex; justify-content: center; padding: 20px;
  .spinner { width: 24px; height: 24px; border: 2px solid rgba($green, 0.2); border-top-color: $green; border-radius: 50%; animation: spin 0.8s linear infinite; }
}
.no-more { text-align: center; padding: 20px; color: $muted; font-size: 12px; }
.sentinel { height: 1px; }

.member-list { display: grid; gap: 12px; }

.member-card {
  padding: 16px; background: $elevated; border-radius: 14px; border: 1px solid $border;

  .member-top {
    display: flex; align-items: center; gap: 12px; margin-bottom: 14px;
  }

  .avatar-circle {
    width: 40px; height: 40px; border-radius: 50%; background: $border;
    display: grid; place-items: center; color: $muted; flex-shrink: 0;
  }

  .member-info {
    flex: 1;
    strong { display: block; font-size: 14px; color: white; font-weight: 600; }
    span { font-size: 11px; color: $muted; }
  }

  .member-date { font-size: 11px; color: $muted; }

  .member-stats {
    display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px;

    .stat-item {
      span { display: block; font-size: 11px; color: $muted; }
      strong { display: block; font-size: 16px; font-weight: 700; color: white; margin-top: 4px; }
    }
  }
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
