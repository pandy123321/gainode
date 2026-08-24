<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePredictionStore } from '../../../stores/prediction'
import { t } from '../../../i18n'
import FiveStateContainer from '../../../components/FiveStateContainer.vue'
import DataStateBadge from '../../../components/DataStateBadge.vue'

const route = useRoute()
const router = useRouter()
const prediction = usePredictionStore()

const marketId = computed(() => String(route.params.id ?? ''))
const detail = computed(() => prediction.marketDetail)

const statusLabel = computed(() => {
  const s = detail.value?.market_status ?? 'open'
  return t(`prediction.market_status.${s}`)
})

function reload() {
  if (marketId.value) prediction.fetchMarketDetail(marketId.value)
}

onMounted(reload)
</script>

<template>
  <main class="prediction-detail">
    <header class="page-header">
      <h1>{{ t('page.m_predict_002.title') }}</h1>
      <DataStateBadge page-id="M-PREDICT-002" />
    </header>

    <FiveStateContainer
      :state="prediction.marketDetailLoading ? 'loading' : prediction.marketDetailError ? 'error' : 'default'"
      :error-message="prediction.marketDetailError || ''"
      @retry="reload"
    >
      <template v-if="!detail">
        <section class="card" data-testid="detail-empty">
          <p class="empty">{{ t('page.m_predict_002.empty') }}</p>
        </section>
      </template>

      <template v-else>
        <!-- Match Hero -->
        <section class="hero" :data-status="detail.market_status" data-testid="detail-hero">
          <p class="hero-kicker">{{ t('page.m_predict_002.description') }}</p>
          <h2 class="hero-status">{{ statusLabel }}</h2>
          <div v-if="detail.lock_at" class="hero-meta">
            {{ t('page.m_predict_001.lock_at') }} {{ new Date(detail.lock_at * 1000).toLocaleString() }}
          </div>
        </section>

        <!-- 三方向同级（无推荐光效） -->
        <section class="card" data-testid="detail-selections">
          <h3 class="card-title">{{ t('page.m_predict_002.selection_title') }}</h3>
          <div class="three-way">
            <div v-for="s in detail.selections || []" :key="s" class="way">
              {{ t(`prediction.selection.${s}`) }}
            </div>
          </div>
        </section>

        <!-- 规则 / AI 参考（Disclosure DTO 未冻结 → 占位，不绑定） -->
        <section class="card" data-testid="detail-rules">
          <h3 class="card-title">{{ t('page.m_predict_002.rules_title') }}</h3>
          <p class="empty">{{ t('page.m_predict_002.rules_placeholder') }}</p>
        </section>
      </template>
    </FiveStateContainer>

    <div class="actions">
      <button
        class="btn-primary"
        data-testid="participate"
        :disabled="!detail"
        @click="router.push(`/prediction/confirm/${marketId}`)"
      >
        {{ t('page.m_predict_002.primary_action') }}
      </button>
    </div>
  </main>
</template>

<style scoped>
.prediction-detail {
  max-width: 640px;
  margin: 0 auto;
  min-height: 100vh;
  background: var(--gray-50);
  color: var(--gray-950);
}
.page-header {
  padding: var(--space-4);
  background: var(--white);
  border-bottom: var(--border-default);
}
.page-header h1 {
  margin: 0;
  font-size: 20px;
  font-weight: 800;
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
  margin: 0 0 var(--space-2);
  font-size: 24px;
  font-weight: 800;
}
.hero-meta {
  font-size: 12px;
  color: var(--gray-500);
}
.card {
  margin: var(--space-3) var(--space-4) 0;
  padding: var(--space-4);
  background: var(--white);
  border: var(--border-default);
  border-radius: var(--radius-lg);
}
.card-title {
  margin: 0 0 var(--space-3);
  font-size: 15px;
  font-weight: 700;
  color: var(--gray-800);
}
.three-way {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: var(--space-2);
}
.way {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 44px;
  background: var(--gray-50);
  border: var(--border-default);
  border-radius: var(--radius-md);
  font-size: 15px;
  font-weight: 700;
  color: var(--gray-800);
}
.empty {
  margin: 0;
  color: var(--gray-400);
  font-size: 13px;
  text-align: center;
}
.actions {
  margin: var(--space-4);
}
.btn-primary {
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
.btn-primary:disabled {
  background: var(--gray-300);
  cursor: not-allowed;
}
</style>
