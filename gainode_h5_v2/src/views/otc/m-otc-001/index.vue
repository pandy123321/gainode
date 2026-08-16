<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useOtcStore } from '../../../stores/otc'
import { usePowerStore } from '../../../stores/power'
import { t } from '../../../i18n'
import FiveStateContainer from '../../../components/FiveStateContainer.vue'
import type { OtcOrder } from '../../../api/otc'

const otc = useOtcStore()
const power = usePowerStore()

const position = computed(() => power.position)

function sideLabel(o: OtcOrder) {
  return t(`otc.side.${o.side}`)
}
function statusLabel(o: OtcOrder) {
  return t(`otc.status.${o.status}`)
}

onMounted(() => {
  if (!otc.orderBook.length) otc.fetchOrderBook()
  if (!power.hasPosition) power.fetch()
})
</script>

<template>
  <main class="otc-root">
    <header class="page-header">
      <h1>{{ t('page.m_otc_001.title') }}</h1>
      <RouterLink to="/otc/my" class="header-link" data-testid="my-orders-link">
        {{ t('page.m_otc_001.my_orders') }}
      </RouterLink>
    </header>

    <!-- 首屏挂买/挂卖双 CTA（fail-closed，不开放真实挂单） -->
    <section class="card" data-testid="otc-quick">
      <div class="otc-grid">
        <button class="otc-btn" disabled data-testid="otc-buy">{{ t('page.m_otc_001.otc_buy') }}</button>
        <button class="otc-btn" disabled data-testid="otc-sell">{{ t('page.m_otc_001.otc_sell') }}</button>
      </div>
      <p class="hint">{{ t('page.m_otc_001.otc_restricted') }}</p>
    </section>

    <!-- Capacity / Power Summary（Power 只读展示；Capacity 无端点 → 受限） -->
    <section class="card" data-testid="otc-capacity">
      <h3 class="card-title">{{ t('page.m_otc_001.capacity_title') }}</h3>
      <div v-if="position" class="row">
        <span class="row-label">{{ t('power.available') }}</span>
        <span class="row-value">{{ position.available }}</span>
      </div>
      <p class="hint">{{ t('page.m_otc_001.capacity_restricted') }}</p>
    </section>

    <!-- 市场参考信息（订单簿只读；参考价无来源端点 → 受限） -->
    <section class="card" data-testid="otc-orderbook">
      <h3 class="card-title">{{ t('page.m_otc_001.orderbook_title') }}</h3>
      <FiveStateContainer
        :state="otc.bookLoading ? 'loading' : otc.bookError ? 'error' : 'default'"
        :error-message="otc.bookError || ''"
        @retry="otc.fetchOrderBook"
      >
        <div v-if="otc.orderBook.length" class="book-list">
          <div v-for="o in otc.orderBook" :key="o.otc_order_id" class="book-row">
            <span class="book-side" :data-side="o.side">{{ sideLabel(o) }}</span>
            <span class="book-price">{{ o.price ?? '-' }}</span>
            <span class="book-qty">{{ o.quantity_apt ?? '-' }}</span>
            <span class="book-status">{{ statusLabel(o) }}</span>
          </div>
        </div>
        <p v-else class="empty" data-testid="orderbook-empty">{{ t('page.m_otc_001.orderbook_empty') }}</p>
      </FiveStateContainer>
      <p class="hint">{{ t('page.m_otc_001.reference_price_notice') }}</p>
    </section>

    <!-- 流动性风险说明（文案，不伪造数据） -->
    <section class="card" data-testid="otc-liquidity">
      <p class="hint">{{ t('page.m_otc_001.liquidity_notice') }}</p>
    </section>
  </main>
</template>

<style scoped>
.otc-root {
  max-width: 640px;
  margin: 0 auto;
  min-height: 100vh;
  background: var(--gray-50);
  color: var(--gray-950);
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
.otc-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: var(--space-3);
}
.otc-btn {
  height: 48px;
  border: none;
  border-radius: var(--radius-lg);
  background: var(--brand-blue-600);
  color: var(--white);
  font-size: 16px;
  font-weight: 700;
}
.otc-btn:disabled {
  background: var(--gray-200);
  color: var(--gray-500);
  cursor: not-allowed;
}
.hint {
  margin: var(--space-2) 0 0;
  color: var(--gray-400);
  font-size: 12px;
  text-align: center;
  line-height: 1.6;
}
.row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  min-height: 44px;
  border-bottom: 1px solid var(--gray-100);
}
.row-label {
  color: var(--gray-500);
  font-size: 14px;
}
.row-value {
  font-weight: 700;
  font-size: 14px;
}
.book-list {
  display: flex;
  flex-direction: column;
}
.book-row {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  min-height: 44px;
  border-bottom: 1px solid var(--gray-100);
  font-size: 13px;
}
.book-row:last-child {
  border-bottom: none;
}
.book-side {
  min-width: 40px;
  font-weight: 700;
}
.book-side[data-side='BUY'] {
  color: var(--brand-blue-700);
}
.book-side[data-side='SELL'] {
  color: var(--gray-600);
}
.book-price {
  flex: 1;
  text-align: right;
  font-weight: 700;
}
.book-qty {
  flex: 1;
  text-align: right;
  color: var(--gray-600);
}
.book-status {
  min-width: 56px;
  text-align: right;
  color: var(--gray-500);
  font-size: 11px;
}
.empty {
  margin: var(--space-3) 0 0;
  color: var(--gray-400);
  font-size: 13px;
  text-align: center;
}
</style>
