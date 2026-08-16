<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useEntitlementStore } from '../../../stores/entitlement'
import { useRobotStore } from '../../../stores/robot'
import { usePredictionStore } from '../../../stores/prediction'
import { useAssetStore } from '../../../stores/asset'
import { useNoticeStore } from '../../../stores/notice'
import { t } from '../../../i18n'
import FiveStateContainer from '../../../components/FiveStateContainer.vue'
import BottomNav from '../../../components/BottomNav.vue'
import type { PredictionMarket } from '../../../api/prediction'

const router = useRouter()
const ent = useEntitlementStore()
const robot = useRobotStore()
const prediction = usePredictionStore()
const asset = useAssetStore()
const notice = useNoticeStore()

// ---- Admission（资格受限 → 进入 KYC）----
const admissionRestricted = computed(
  () =>
    ent.loaded &&
    (ent.globalP != null && !ent.globalP.allowed) &&
    (ent.ai != null && !ent.ai.allowed),
)

// ---- 主 CTA：只做导航，不在 Home 内完成高风险写操作（07 §S03-P02）----
const primaryAction = computed(() => {
  if (ent.error == null && admissionRestricted.value) return { label: t('page.m_home_001.cta_kyc'), to: '/kyc' }
  if (robot.loaded && robot.hasRobot && robot.summary?.status === 'inactive')
    return { label: t('page.m_home_001.cta_start'), to: '/robot' }
  return { label: t('page.m_home_001.primary_action'), to: '/robot' }
})

const robotStatusLabel = computed(() => {
  const s = robot.summary?.status ?? 'inactive'
  return t(`robot.status.${s}`)
})

// ---- 热门竞猜（仅 open/closing，避免展示锁定/结算态）----
const featured = computed<PredictionMarket[]>(() => prediction.featuredMarkets.slice(0, 5))

// ---- NoticeTicker 单行 ----
const tickerText = computed(() => {
  if (notice.error) return ''
  const latest = notice.items[0]
  if (!latest) return t('page.m_home_001.notice_empty')
  const title = t(latest.title_key)
  return title !== latest.title_key ? title : t(`notice.type.${latest.notice_type}`)
})

const assetBalance = computed(() => asset.balance?.effective_available ?? null)

function reloadAll() {
  ent.fetch()
  robot.fetch()
  prediction.fetchMarkets()
  asset.fetch()
  notice.fetch()
}

onMounted(reloadAll)
</script>

<template>
  <main class="home">
    <!-- Header -->
    <header class="home-header">
      <h1>{{ t('page.m_home_001.title') }}</h1>
      <RouterLink to="/notices" class="bell" :data-unread="notice.unreadCount" data-testid="home-bell" aria-label="消息">
        <span v-if="notice.unreadCount > 0" class="bell-badge">{{ notice.unreadCount }}</span>
      </RouterLink>
    </header>

    <!-- Hero / 今日状态 -->
    <section class="hero" :data-restricted="admissionRestricted">
      <p class="hero-kicker">{{ t('page.m_home_001.description') }}</p>
      <h2 class="hero-status">
        <template v-if="admissionRestricted">{{ t('page.m_home_001.hero_restricted') }}</template>
        <template v-else-if="robot.loaded && robot.hasRobot">{{ robotStatusLabel }}</template>
        <template v-else>{{ t('page.m_home_001.hero_robot_none') }}</template>
      </h2>
      <button class="cta" @click="router.push(primaryAction.to)">
        {{ primaryAction.label }}
      </button>
    </section>

    <!-- NoticeTicker -->
    <RouterLink v-if="tickerText" to="/notices" class="ticker" data-testid="home-ticker">
      <span class="ticker-text">{{ tickerText }}</span>
    </RouterLink>

    <!-- Robot 回访 -->
    <section class="card" data-testid="home-robot">
      <div class="card-head">
        <h3 class="card-title">{{ t('page.m_home_001.section_robot') }}</h3>
        <RouterLink to="/robot" class="card-link">{{ t('page.m_home_001.robot_view') }}</RouterLink>
      </div>
      <FiveStateContainer
        :state="robot.loading ? 'loading' : robot.error ? 'error' : 'default'"
        :error-message="robot.error || ''"
        @retry="robot.fetch"
      >
        <template v-if="!robot.hasRobot">
          <p class="card-empty">{{ t('page.m_home_001.robot_empty') }}</p>
        </template>
        <template v-else>
          <div class="robot-row">
            <span class="robot-label">{{ t('page.m_home_001.robot_level') }}</span>
            <span class="robot-value">Lv.{{ robot.summary?.level ?? '-' }}</span>
          </div>
          <div class="robot-row">
            <span class="robot-label">{{ t('page.m_home_001.robot_capacity') }}</span>
            <span class="robot-value">{{ robot.summary?.standard_capacity ?? '-' }}</span>
          </div>
        </template>
      </FiveStateContainer>
    </section>

    <!-- 热门竞猜 -->
    <section class="card" data-testid="home-markets">
      <div class="card-head">
        <h3 class="card-title">{{ t('page.m_home_001.section_markets') }}</h3>
        <RouterLink to="/prediction" class="card-link">{{ t('page.m_home_001.markets_more') }}</RouterLink>
      </div>
      <FiveStateContainer
        :state="prediction.loading ? 'loading' : prediction.error ? 'error' : 'default'"
        :error-message="prediction.error || ''"
        @retry="prediction.fetchMarkets"
      >
        <div v-if="featured.length" class="market-list">
          <RouterLink
            v-for="m in featured"
            :key="m.market_id"
            to="/prediction"
            class="market-row"
          >
            <span class="market-selections">{{ (m.selections || []).join(' / ') }}</span>
            <span class="market-cta">{{ t('page.m_home_001.markets_participate') }}</span>
          </RouterLink>
        </div>
        <p v-else class="card-empty" data-testid="markets-empty">{{ t('page.m_home_001.markets_empty') }}</p>
      </FiveStateContainer>
    </section>

    <!-- APT/Power/OTC 快捷入口 -->
    <section class="quick-grid" data-testid="home-quick">
      <RouterLink to="/asset" class="quick-card">
        <span class="quick-name">{{ t('page.m_home_001.quick_apt') }}</span>
      </RouterLink>
      <RouterLink to="/power" class="quick-card">
        <span class="quick-name">{{ t('page.m_home_001.quick_power') }}</span>
      </RouterLink>
      <RouterLink to="/me" class="quick-card">
        <span class="quick-name">{{ t('page.m_home_001.quick_otc') }}</span>
      </RouterLink>
    </section>

    <!-- AI 数据摘要（APT 余额，权威只读） -->
    <section class="card" data-testid="home-ai">
      <h3 class="card-title">{{ t('page.m_home_001.section_ai') }}</h3>
      <FiveStateContainer
        :state="asset.loading ? 'loading' : asset.error ? 'error' : 'default'"
        :error-message="asset.error || ''"
        @retry="asset.fetch"
      >
        <div class="ai-balance">
          <span class="ai-label">{{ t('page.m_home_001.apt_balance') }}</span>
          <span class="ai-value">{{ assetBalance ?? '-' }}</span>
        </div>
      </FiveStateContainer>
    </section>

    <!-- UpgradeLeaderboard（契约缺口：无端点，不伪造数据） -->
    <section class="card" data-testid="home-leaderboard">
      <h3 class="card-title">{{ t('page.m_home_001.section_leaderboard') }}</h3>
      <p class="card-empty">{{ t('page.m_home_001.leaderboard_empty') }}</p>
    </section>

    <BottomNav active="home" />
  </main>
</template>

<style scoped>
.home {
  max-width: 640px;
  margin: 0 auto;
  padding-bottom: 0;
  background: var(--gray-50);
  min-height: 100vh;
  color: var(--gray-950);
}
.home-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--space-4);
  background: var(--white);
  border-bottom: var(--border-default);
}
.home-header h1 {
  margin: 0;
  font-size: 20px;
  font-weight: 800;
}
.bell {
  position: relative;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--gray-100);
}
.bell::before {
  content: '🔔';
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  font-size: 20px;
}
.bell-badge {
  position: absolute;
  top: 2px;
  right: 2px;
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  border-radius: 999px;
  background: var(--danger-600);
  color: var(--white);
  font-size: 11px;
  font-weight: 700;
  display: grid;
  place-items: center;
}
.hero {
  padding: var(--space-6) var(--space-4);
  background: linear-gradient(180deg, var(--brand-blue-50), var(--white));
}
.hero-kicker {
  margin: 0 0 var(--space-2);
  color: var(--gray-500);
  font-size: 13px;
}
.hero-status {
  margin: 0 0 var(--space-4);
  font-size: 24px;
  font-weight: 800;
}
.hero[data-restricted='true'] .hero-status {
  color: var(--warning-700);
}
.cta {
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
.ticker {
  display: block;
  margin: var(--space-3) var(--space-4) 0;
  padding: var(--space-2) var(--space-3);
  min-height: 40px;
  background: var(--white);
  border: var(--border-default);
  border-radius: var(--radius-md);
  text-decoration: none;
  color: var(--gray-700);
  overflow: hidden;
}
.ticker-text {
  font-size: 13px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  display: block;
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
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  color: var(--gray-800);
}
.card-link {
  font-size: 13px;
  color: var(--brand-blue-600);
  text-decoration: none;
}
.card-empty {
  margin: var(--space-3) 0 0;
  color: var(--gray-400);
  font-size: 13px;
  text-align: center;
}
.robot-row {
  display: flex;
  justify-content: space-between;
  min-height: 40px;
  align-items: center;
  border-bottom: 1px solid var(--gray-100);
}
.robot-row:last-child {
  border-bottom: none;
}
.robot-label {
  color: var(--gray-500);
  font-size: 14px;
}
.robot-value {
  font-weight: 700;
  font-size: 14px;
}
.market-list {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}
.market-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 44px;
  padding: 0 var(--space-3);
  background: var(--gray-50);
  border-radius: var(--radius-md);
  text-decoration: none;
  color: var(--gray-800);
}
.market-selections {
  font-size: 14px;
  font-weight: 600;
}
.market-cta {
  font-size: 12px;
  color: var(--brand-blue-600);
}
.quick-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--space-3);
  margin: var(--space-3) var(--space-4) 0;
}
.quick-card {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 44px;
  background: var(--white);
  border: var(--border-default);
  border-radius: var(--radius-md);
  text-decoration: none;
}
.quick-name {
  font-size: 14px;
  font-weight: 700;
  color: var(--gray-800);
}
.ai-balance {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 48px;
}
.ai-label {
  color: var(--gray-500);
  font-size: 14px;
}
.ai-value {
  font-size: 20px;
  font-weight: 800;
  color: var(--gray-900);
}
</style>
