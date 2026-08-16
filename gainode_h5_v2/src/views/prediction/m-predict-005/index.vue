<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePredictionStore } from '../../../stores/prediction'
import { t } from '../../../i18n'
import FiveStateContainer from '../../../components/FiveStateContainer.vue'

const route = useRoute()
const router = useRouter()
const prediction = usePredictionStore()

const orderId = computed(() => String(route.params.id ?? ''))
const order = computed(() => prediction.orderReceipt)

const statusLabel = computed(() => {
  const s = order.value?.order_status ?? 'submitted'
  return t(`prediction.order_status.${s}`)
})

const selectionLabel = computed(() => {
  const s = order.value?.selection
  return s ? t(`prediction.selection.${s}`) : '-'
})

function reload() {
  if (orderId.value) prediction.fetchOrderReceipt(orderId.value)
}

onMounted(reload)
</script>

<template>
  <main class="order-detail">
    <header class="page-header">
      <h1>{{ t('page.m_predict_005.title') }}</h1>
    </header>

    <FiveStateContainer
      :state="prediction.orderReceiptLoading ? 'loading' : prediction.orderReceiptError ? 'error' : 'default'"
      :error-message="prediction.orderReceiptError || ''"
      @retry="reload"
    >
      <section v-if="order" class="result" data-testid="order-result">
        <div class="result-hero" :data-status="order.order_status">
          <p class="result-kicker">{{ t('page.m_predict_005.description') }}</p>
          <h2 class="result-status">{{ statusLabel }}</h2>
        </div>

        <div class="card">
          <div class="row">
            <span class="row-label">{{ t('page.m_predict_005.selection') }}</span>
            <span class="row-value">{{ selectionLabel }}</span>
          </div>
          <div class="row">
            <span class="row-label">{{ t('page.m_predict_005.amount_apt') }}</span>
            <span class="row-value">{{ order.amount_apt }}</span>
          </div>
          <div class="row">
            <span class="row-label">{{ t('page.m_predict_005.order_id') }}</span>
            <span class="row-value mono">{{ order.order_id }}</span>
          </div>
        </div>

        <!-- 赛果与结算双轨：PredictionOrder 无 result_id/settlement_id → 占位，不伪造 -->
        <div class="card">
          <h3 class="card-title">{{ t('page.m_predict_005.result_settlement_title') }}</h3>
          <p class="empty">{{ t('page.m_predict_005.result_settlement_placeholder') }}</p>
        </div>
      </section>
    </FiveStateContainer>

    <div class="actions">
      <button class="btn-primary" data-testid="back" @click="router.push('/prediction')">
        {{ t('page.m_predict_005.primary_action') }}
      </button>
    </div>
  </main>
</template>

<style scoped>
.order-detail {
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
.result-hero {
  padding: var(--space-6) var(--space-4);
  background: linear-gradient(180deg, var(--brand-blue-50), var(--white));
}
.result-kicker {
  margin: 0 0 var(--space-2);
  color: var(--gray-500);
  font-size: 13px;
}
.result-status {
  margin: 0;
  font-size: 24px;
  font-weight: 800;
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
.row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  min-height: 44px;
  border-bottom: 1px solid var(--gray-100);
}
.row:last-child {
  border-bottom: none;
}
.row-label {
  color: var(--gray-500);
  font-size: 14px;
}
.row-value {
  font-weight: 700;
  font-size: 14px;
}
.row-value.mono {
  font-family: var(--font-mono, monospace);
  font-size: 12px;
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
</style>
