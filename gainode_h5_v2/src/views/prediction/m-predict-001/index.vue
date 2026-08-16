<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { usePredictionStore } from '../../../stores/prediction'
import { t } from '../../../i18n'
import FiveStateContainer from '../../../components/FiveStateContainer.vue'
import BottomNav from '../../../components/BottomNav.vue'
import type { PredictionMarket } from '../../../api/prediction'

const router = useRouter()
const prediction = usePredictionStore()

const statusLabel = (m: PredictionMarket) => t(`prediction.market_status.${m.market_status}`)

function selectionLabel(s: string) {
  const key = `prediction.selection.${s}`
  const label = t(key)
  return label === key ? s : label
}

const selectionsLabel = (m: PredictionMarket) =>
  (m.selections || []).map(selectionLabel).join(' / ')

function reload() {
  prediction.fetchMarkets()
}

function open(id: string) {
  router.push(`/prediction/${id}`)
}

onMounted(() => {
  if (!prediction.loaded) reload()
})
</script>

<template>
  <main class="prediction-root">
    <header class="page-header">
      <h1>{{ t('page.m_predict_001.title') }}</h1>
      <RouterLink to="/prediction/my" class="header-link" data-testid="my-orders">
        {{ t('page.m_predict_001.my_orders') }}
      </RouterLink>
    </header>

    <FiveStateContainer
      :state="prediction.loading ? 'loading' : prediction.error ? 'error' : 'default'"
      :error-message="prediction.error || ''"
      @retry="reload"
    >
      <template v-if="!prediction.markets.length">
        <section class="card" data-testid="markets-empty">
          <p class="empty">{{ t('page.m_predict_001.empty') }}</p>
        </section>
      </template>

      <template v-else>
        <!-- 热门（open） -->
        <section v-if="prediction.openMarkets.length" class="section">
          <h2 class="section-title">{{ t('page.m_predict_001.section_hot') }}</h2>
          <button
            v-for="m in prediction.openMarkets"
            :key="m.market_id"
            class="market-card"
            data-testid="market-card"
            @click="open(m.market_id)"
          >
            <div class="market-head">
              <span class="market-selections">{{ selectionsLabel(m) }}</span>
              <span class="market-status" :data-status="m.market_status">{{ statusLabel(m) }}</span>
            </div>
            <div class="market-meta">
              <span v-if="m.lock_at" class="meta-item">
                {{ t('page.m_predict_001.lock_at') }} {{ new Date(m.lock_at * 1000).toLocaleString() }}
              </span>
            </div>
          </button>
        </section>

        <!-- 即将截止（closing） -->
        <section v-if="prediction.closingMarkets.length" class="section">
          <h2 class="section-title">{{ t('page.m_predict_001.section_closing') }}</h2>
          <button
            v-for="m in prediction.closingMarkets"
            :key="m.market_id"
            class="market-card"
            data-testid="market-card"
            @click="open(m.market_id)"
          >
            <div class="market-head">
              <span class="market-selections">{{ selectionsLabel(m) }}</span>
              <span class="market-status" :data-status="m.market_status">{{ statusLabel(m) }}</span>
            </div>
            <div class="market-meta">
              <span v-if="m.lock_at" class="meta-item">
                {{ t('page.m_predict_001.lock_at') }} {{ new Date(m.lock_at * 1000).toLocaleString() }}
              </span>
            </div>
          </button>
        </section>
      </template>
    </FiveStateContainer>

    <BottomNav active="prediction" />
  </main>
</template>

<style scoped>
.prediction-root {
  max-width: 640px;
  margin: 0 auto;
  min-height: 100vh;
  background: var(--gray-50);
  color: var(--gray-950);
  padding-bottom: 72px;
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
.section {
  margin: var(--space-3) var(--space-4) 0;
}
.section-title {
  margin: 0 0 var(--space-2);
  font-size: 15px;
  font-weight: 700;
  color: var(--gray-800);
}
.market-card {
  display: block;
  width: 100%;
  min-height: 128px;
  margin-bottom: var(--space-2);
  padding: var(--space-4);
  text-align: left;
  background: var(--white);
  border: var(--border-default);
  border-radius: var(--radius-lg);
  cursor: pointer;
}
.market-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--space-3);
}
.market-selections {
  font-size: 16px;
  font-weight: 700;
  color: var(--gray-900);
}
.market-status {
  padding: 2px 10px;
  font-size: 12px;
  font-weight: 600;
  border-radius: 999px;
  background: var(--gray-100);
  color: var(--gray-600);
}
.market-status[data-status='open'] {
  background: var(--brand-blue-50);
  color: var(--brand-blue-700);
}
.market-status[data-status='closing'] {
  background: var(--warning-50);
  color: var(--warning-700);
}
.market-meta {
  font-size: 12px;
  color: var(--gray-500);
}
.meta-item {
  display: inline-block;
}
.card {
  margin: var(--space-3) var(--space-4) 0;
  padding: var(--space-4);
  background: var(--white);
  border: var(--border-default);
  border-radius: var(--radius-lg);
}
.empty {
  margin: 0;
  color: var(--gray-400);
  font-size: 13px;
  text-align: center;
}
</style>
