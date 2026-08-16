<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ProjectApi, TeamApi } from '../../api/services'
import { t } from '../../i18n'

const router = useRouter()
const teamCnt = ref('0')
const teamIncome = ref('0.00')
const inviteCode = ref('')
const selectedLevel = ref(0)
const incomeLogs = ref<any[]>([])
const incomeLoading = ref(true)
const page = ref(1)
const hasMore = ref(true)

onMounted(() => {
  loadTeamDetail()
  loadIncomeLogs()
})

async function loadTeamDetail() {
  const res = await TeamApi.getTeamDetail()
  if (res.code === 0 && res.data) {
    const data = res.data
    teamCnt.value = data.team_cnt?.toString() || '0'
    teamIncome.value = (parseFloat(data.team_income_money?.toString() || '0') || 0).toFixed(2)
    inviteCode.value = data.invite_code?.toString() || ''
  }
}

async function loadIncomeLogs() {
  page.value = 1
  incomeLoading.value = incomeLogs.value.length === 0
  const res = await ProjectApi.getIncomeLogs(selectedLevel.value + 1, page.value)
  if (res.code === 0 && res.data) {
    const data = res.data
    const list: any[] = data.data || data || []
    incomeLogs.value = list
    hasMore.value = (parseInt(data.total_page?.toString() || '0') || 0) > page.value
  }
  incomeLoading.value = false
}

async function loadMore() {
  if (!hasMore.value) return
  page.value++
  const res = await ProjectApi.getIncomeLogs(selectedLevel.value + 1, page.value)
  if (res.code === 0 && res.data) {
    const data = res.data
    const list: any[] = data.data || data || []
    incomeLogs.value.push(...list)
    hasMore.value = (parseInt(data.total_page?.toString() || '0') || 0) > page.value
  }
}

function switchLevel(idx: number) {
  selectedLevel.value = idx
  loadIncomeLogs()
}
</script>

<template>
  <div class="team-screen">
    <h2>{{ t('build_your_ai_network') }}</h2>
    <p class="subtitle">{{ t('grow_together_earn_together') }}</p>

    <div class="stats-row">
      <div class="stat-card">
        <span>{{ t('total_members') }}</span>
        <strong>{{ teamCnt }}</strong>
      </div>
      <div class="stat-card">
        <span>{{ t('total_earnings') }}</span>
        <strong>{{ teamIncome }} <small>USDT</small></strong>
      </div>
    </div>

    <img src="/images/photo1.jpg" alt="" class="promo-img" />

    <!-- Invite Card -->
    <div class="invite-card">
      <div class="invite-icon">
        <svg viewBox="0 0 24 24" fill="none" width="24" height="24"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2" stroke="currentColor" stroke-width="1.8"/><circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="1.8"/><path d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke="currentColor" stroke-width="1.8"/></svg>
      </div>
      <div class="invite-text">
        <strong>{{ t('invite_ai_partner') }}</strong>
        <span>{{ t('earn_commission_share_rewards') }}</span>
      </div>
      <button class="invite-btn" @click="router.push('/invite')">{{ t('invite_now') }}</button>
    </div>

    <img src="/images/photo2.jpg" alt="" class="promo-img" />

    <!-- Member Earnings -->
    <div class="section-header">
      <h3>{{ t('member_earnings') }}</h3>
      <button class="link-btn" @click="router.push('/my-team')">{{ t('team_members') }}</button>
    </div>

    <div class="level-tabs">
      <button v-for="(label, i) in [t('level_one'), t('level_two'), t('level_three')]" :key="i"
        class="level-tab" :class="{ active: selectedLevel === i }" @click="switchLevel(i)">
        {{ label }}
      </button>
    </div>

    <p class="desc-text">{{ t('daily_earnings_detail_latest_first') }}</p>

    <div v-if="incomeLoading" class="loading-wrap"><div class="spinner"></div></div>
    <div v-else-if="!incomeLogs.length" class="empty-text">{{ t('empty') }}</div>
    <div v-else class="income-list">
      <div v-for="(log, i) in incomeLogs" :key="i" class="income-card">
        <div class="avatar-circle">{{ (log.account || log.user_no || '?')[0] }}</div>
        <div class="income-info">
          <strong>{{ log.account || log.user_no || '-' }}</strong>
          <span>UID:{{ log.user_no || '-' }} · 第{{ log.to_day || '-' }}天</span>
        </div>
        <div class="income-amount">
          <strong>+{{ (parseFloat(log.income_amount?.toString() || '0') || 0).toFixed(2) }} USDT</strong>
          <span>{{ log.income_day || '' }}</span>
        </div>
      </div>
      <button v-if="hasMore" class="load-more-btn" @click="loadMore">加载更多...</button>
    </div>
  </div>
</template>

<style scoped lang="scss">
$elevated: #0E1620;
$border: #1E2830;
$green: #3DDC97;
$muted: #8A9CB0;

.team-screen {
  padding: 0 16px 100px;

  h2 { font-size: 20px; font-weight: 700; color: white; margin: 0; }
  .subtitle { font-size: 12px; color: $muted; margin: 4px 0 20px; }
}

.stats-row {
  display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;

  .stat-card {
    padding: 10px; background: $elevated; border-radius: 12px; border: 1px solid $border;
    span { display: block; font-size: 11px; color: $muted; }
    strong { display: block; font-size: 28px; font-weight: 700; color: $green; margin-top: 8px;
      small { font-size: 13px; font-weight: 600; }
    }
  }
}

.promo-img {
  width: 100%; border-radius: 12px; margin-bottom: 12px; display: block;
}

.invite-card {
  display: flex; align-items: center; gap: 14px; padding: 12px;
  background: $elevated; border-radius: 12px; border: 1px solid $border; margin-bottom: 12px;

  .invite-icon {
    width: 48px; height: 48px; display: grid; place-items: center;
    background: rgba($green, 0.1); border-radius: 10px; color: $green; flex-shrink: 0;
  }
  .invite-text {
    flex: 1; min-width: 0;
    strong { display: block; font-size: 15px; color: white; font-weight: 700; }
    span { font-size: 12px; color: $muted; }
  }
  .invite-btn {
    padding: 8px 16px; background: $green; border: none; border-radius: 20px;
    color: #0A0E14; font-size: 13px; font-weight: 700; cursor: pointer; white-space: nowrap;
  }
}

.section-header {
  display: flex; justify-content: space-between; align-items: center; margin: 20px 0 12px;
  h3 { font-size: 18px; font-weight: 700; color: white; margin: 0; }
  .link-btn { background: none; border: none; color: $green; font-size: 13px; font-weight: 600; cursor: pointer; text-decoration: underline; }
}

.level-tabs {
  display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-bottom: 12px;

  .level-tab {
    padding: 7px; background: none; border: 1px solid $border; border-radius: 20px;
    color: $muted; font-size: 14px; cursor: pointer; text-align: center;
    &.active { background: $green; border-color: $green; color: #0A0E14; font-weight: 700; }
  }
}

.desc-text { font-size: 12px; color: $muted; margin: 0 0 12px; }

.loading-wrap { display: flex; justify-content: center; padding: 20px;
  .spinner { width: 32px; height: 32px; border: 3px solid rgba($green, 0.2); border-top-color: $green; border-radius: 50%; animation: spin 0.8s linear infinite; }
}
.empty-text { text-align: center; padding: 20px; color: $muted; font-size: 13px; }

.income-list { display: grid; gap: 10px; }

.income-card {
  display: flex; align-items: center; gap: 12px; padding: 12px;
  background: $elevated; border-radius: 12px; border: 1px solid $border;

  .avatar-circle {
    width: 44px; height: 44px; border-radius: 50%; background: $border;
    display: grid; place-items: center; font-size: 18px; font-weight: 700; color: white; flex-shrink: 0;
  }
  .income-info {
    flex: 1; min-width: 0;
    strong { display: block; font-size: 14px; color: white; font-weight: 600; }
    span { font-size: 11px; color: $muted; }
  }
  .income-amount {
    text-align: right;
    strong { display: block; font-size: 14px; color: $green; font-weight: 700; }
    span { font-size: 10px; color: $muted; }
  }
}

.load-more-btn {
  display: block; width: 100%; padding: 12px; background: none; border: 1px solid $border;
  border-radius: 10px; color: $muted; font-size: 13px; cursor: pointer;
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
