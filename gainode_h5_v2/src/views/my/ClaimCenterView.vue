<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { showToast } from '../../utils/toast'
import { t } from '../../i18n'

const router = useRouter()

interface ClaimItem {
  amount: string
  date: string
  source: string
  claimed: boolean
}

const filterIndex = ref(0)
const filters = ['filter_all', 'filter_unclaimed', 'filter_claimed']

const items = ref<ClaimItem[]>([
  { amount: '12.40', date: '2026-07-30', source: '团队收益', claimed: false },
  { amount: '8.20', date: '2026-07-29', source: 'Agent G-3', claimed: true },
  { amount: '5.00', date: '2026-07-28', source: 'Agent G-2', claimed: false },
  { amount: '15.60', date: '2026-07-27', source: '邀请奖励', claimed: true },
  { amount: '3.30', date: '2026-07-26', source: 'Agent G-1', claimed: false },
])

const filteredItems = computed(() => {
  if (filterIndex.value === 1) return items.value.filter((i) => !i.claimed)
  if (filterIndex.value === 2) return items.value.filter((i) => i.claimed)
  return items.value
})

const totalUnclaimed = computed(() =>
  items.value
    .filter((i) => !i.claimed)
    .reduce((sum, i) => sum + parseFloat(i.amount), 0)
)

const totalClaimed = computed(() =>
  items.value
    .filter((i) => i.claimed)
    .reduce((sum, i) => sum + parseFloat(i.amount), 0)
)

function getSourceInfo(source: string) {
  if (source.includes('团队')) return { color: '#03A9F4', icon: 'team', label: t('source_team_earnings') }
  if (source.includes('邀请')) return { color: '#FFB800', icon: 'gift', label: t('source_invite_reward') }
  return { color: '#3DDC97', icon: 'agent', label: t('source_agent_earnings') }
}

function handleClaim(item: ClaimItem) {
  showToast('领取成功')
  item.claimed = true
}
</script>

<template>
  <div class="claim-screen">
    <header class="screen-header">
      <button class="back-btn" @click="router.back()">
        <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
      <h1>{{ t('claim_center_title') }}</h1>
    </header>

    <div class="claim-content">
      <!-- Summary -->
      <div class="summary-row">
        <div class="summary-card green-border">
          <span>{{ t('pending_claim') }}</span>
          <strong>{{ totalUnclaimed.toFixed(2) }} USDT</strong>
        </div>
        <div class="summary-card">
          <span>{{ t('total_claimed') }}</span>
          <strong>{{ totalClaimed.toFixed(2) }} USDT</strong>
        </div>
      </div>

      <!-- Filter Tabs -->
      <div class="filter-row">
        <button
          v-for="(f, i) in filters"
          :key="i"
          class="filter-tab"
          :class="{ active: filterIndex === i }"
          @click="filterIndex = i"
        >
          {{ t(f) }}
        </button>
      </div>

      <!-- Claim Items -->
      <div v-if="!filteredItems.length" class="empty-text">{{ t('empty') }}</div>
      <div v-else class="claim-list">
        <div
          v-for="(item, idx) in filteredItems"
          :key="idx"
          class="claim-card"
          :class="{ claimed: item.claimed }"
        >
          <div class="claim-top">
            <div class="source-icon" :style="{ background: getSourceInfo(item.source).color + '1a', color: getSourceInfo(item.source).color }">
              <svg v-if="getSourceInfo(item.source).icon === 'team'" viewBox="0 0 24 24" fill="none" width="18" height="18"><circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.8"/><path d="M6 20c0-3.3 2.7-6 6-6s6 2.7 6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
              <svg v-else-if="getSourceInfo(item.source).icon === 'gift'" viewBox="0 0 24 24" fill="none" width="18" height="18"><rect x="3" y="8" width="18" height="12" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M12 8V6a2 2 0 10-4 0v2h4zm0 0V6a2 2 0 114 0v2h-4z" stroke="currentColor" stroke-width="1.8"/></svg>
              <svg v-else viewBox="0 0 24 24" fill="none" width="18" height="18"><rect x="4" y="8" width="16" height="12" rx="2" stroke="currentColor" stroke-width="1.8"/><circle cx="9" cy="14" r="1.5" fill="currentColor"/><circle cx="15" cy="14" r="1.5" fill="currentColor"/></svg>
            </div>
            <div class="claim-info">
              <strong>{{ item.source }}</strong>
              <span>{{ item.date }}</span>
            </div>
            <strong class="claim-amount" :class="{ muted: item.claimed }">+{{ item.amount }} USDT</strong>
          </div>
          <div class="claim-bottom">
            <span class="source-badge" :style="{ background: getSourceInfo(item.source).color + '1a', color: getSourceInfo(item.source).color }">
              {{ getSourceInfo(item.source).label }}
            </span>
            <div class="claim-spacer"></div>
            <button v-if="item.claimed" class="claimed-btn" disabled>
              <svg viewBox="0 0 16 16" fill="none" width="14" height="14"><path d="M3 8l3 3 7-7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
              {{ t('claimed_status') }}
            </button>
            <button v-else class="claim-action-btn" @click="handleClaim(item)">{{ t('claim') }}</button>
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
$muted: #8A9CB0;

.claim-screen {
  min-height: 100vh;
  background: #0A0E14;
}

.screen-header {
  position: sticky;
  top: 0;
  z-index: 50;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  background: rgba(14, 22, 32, 0.95);
  backdrop-filter: blur(10px);

  .back-btn {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
  }

  h1 {
    font-size: 18px;
    font-weight: 700;
    color: white;
    margin: 0;
  }
}

.claim-content {
  padding: 16px;
}

.summary-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 16px;

  .summary-card {
    padding: 16px;
    background: $elevated;
    border-radius: 12px;
    border: 1px solid $border;

    &.green-border {
      border-color: rgba($green, 0.3);
    }

    span {
      display: block;
      font-size: 12px;
      color: $muted;
    }

    strong {
      display: block;
      font-size: 22px;
      font-weight: 700;
      color: $green;
      margin-top: 8px;
    }
  }
}

.filter-row {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 8px;
  margin-bottom: 16px;

  .filter-tab {
    padding: 10px;
    background: none;
    border: 1px solid $border;
    border-radius: 20px;
    color: $muted;
    font-size: 13px;
    cursor: pointer;
    text-align: center;

    &.active {
      background: $green;
      border-color: $green;
      color: #0A0E14;
      font-weight: 600;
    }
  }
}

.empty-text {
  text-align: center;
  padding: 60px 0;
  color: $muted;
  font-size: 14px;
}

.claim-list {
  display: grid;
  gap: 12px;
}

.claim-card {
  padding: 16px;
  background: $elevated;
  border-radius: 14px;
  border: 1px solid rgba($green, 0.2);

  &.claimed {
    border-color: $border;
  }

  .claim-top {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .source-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
  }

  .claim-info {
    flex: 1;
    min-width: 0;

    strong {
      display: block;
      font-size: 14px;
      font-weight: 600;
      color: white;
    }

    span {
      font-size: 11px;
      color: $muted;
    }
  }

  .claim-amount {
    font-size: 18px;
    font-weight: 700;
    color: $green;

    &.muted {
      color: $muted;
    }
  }

  .claim-bottom {
    display: flex;
    align-items: center;
    margin-top: 12px;

    .source-badge {
      padding: 4px 8px;
      border-radius: 6px;
      font-size: 10px;
      font-weight: 600;
    }

    .claim-spacer {
      flex: 1;
    }

    .claimed-btn {
      display: flex;
      align-items: center;
      gap: 4px;
      padding: 8px 14px;
      background: $border;
      border: none;
      border-radius: 20px;
      color: $muted;
      font-size: 12px;
      cursor: default;
    }

    .claim-action-btn {
      padding: 8px 20px;
      background: $green;
      border: none;
      border-radius: 20px;
      color: #0A0E14;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
    }
  }
}
</style>
