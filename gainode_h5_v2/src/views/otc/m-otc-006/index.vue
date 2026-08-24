<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useOtcStore } from '../../../stores/otc'
import { t } from '../../../i18n'
import FiveStateContainer from '../../../components/FiveStateContainer.vue'
import type { OtcOrder } from '../../../api/otc'
import DataStateBadge from '../../../components/DataStateBadge.vue'

const route = useRoute()
const router = useRouter()
const otc = useOtcStore()

const orderId = computed(() => String(route.params.id ?? ''))
const order = computed<OtcOrder | null>(() => otc.order)
const trades = computed(() => otc.tradesByOrder(orderId.value))

function sideLabel(o: OtcOrder) {
  return t(`otc.side.${o.side}`)
}
function statusLabel(o: OtcOrder) {
  return t(`otc.status.${o.status}`)
}

onMounted(() => {
  if (orderId.value) {
    otc.fetchOrderDetail(orderId.value)
    otc.fetchTrades()
  }
})
</script>

<template>
  <main class="otc-detail">
    <header class="page-header">
      <h1>{{ t('page.m_otc_006.title') }}</h1>
      <DataStateBadge page-id="M-OTC-006" />
    </header>

    <FiveStateContainer
      :state="otc.detailLoading ? 'loading' : otc.detailError ? 'error' : 'default'"
      :error-message="otc.detailError || ''"
      @retry="otc.fetchOrderDetail(orderId)"
    >
      <section v-if="order" class="detail" data-testid="otc-detail">
        <div class="hero" :data-side="order.side">
          <p class="hero-kicker">{{ t('page.m_otc_006.description') }}</p>
          <h2 class="hero-status">{{ sideLabel(order) }} · {{ statusLabel(order) }}</h2>
          <p class="hero-id">{{ order.otc_order_id }}</p>
        </div>

        <div class="card" data-testid="otc-facts">
          <div class="row">
            <span class="row-label">{{ t('page.m_otc_006.side') }}</span>
            <span class="row-value">{{ sideLabel(order) }}</span>
          </div>
          <div class="row">
            <span class="row-label">{{ t('page.m_otc_006.price') }}</span>
            <span class="row-value">{{ order.price ?? '-' }}</span>
          </div>
          <div class="row">
            <span class="row-label">{{ t('page.m_otc_006.quantity') }}</span>
            <span class="row-value">{{ order.quantity_apt ?? '-' }}</span>
          </div>
          <div class="row">
            <span class="row-label">{{ t('page.m_otc_006.filled') }}</span>
            <span class="row-value">{{ order.filled_quantity_apt ?? '0' }}</span>
          </div>
          <div class="row">
            <span class="row-label">{{ t('page.m_otc_006.remaining') }}</span>
            <span class="row-value">{{ order.remaining_quantity_apt ?? '-' }}</span>
          </div>
          <div v-if="order.fee_apt" class="row">
            <span class="row-label">{{ t('page.m_otc_006.fee') }}</span>
            <span class="row-value">{{ order.fee_apt }}</span>
          </div>
        </div>

        <!-- Power 影响（Sell）：展示服务端下发的冻结/消耗，不自算 -->
        <div
          v-if="order.side === 'SELL' && (order.power_required || order.power_frozen || order.power_consumed)"
          class="card"
          data-testid="otc-power"
        >
          <h3 class="card-title">{{ t('page.m_otc_006.power_title') }}</h3>
          <div v-if="order.power_required" class="row">
            <span class="row-label">{{ t('page.m_otc_006.power_required') }}</span>
            <span class="row-value">{{ order.power_required }}</span>
          </div>
          <div v-if="order.power_frozen" class="row">
            <span class="row-label">{{ t('page.m_otc_006.power_frozen') }}</span>
            <span class="row-value">{{ order.power_frozen }}</span>
          </div>
          <div v-if="order.power_consumed" class="row">
            <span class="row-label">{{ t('page.m_otc_006.power_consumed') }}</span>
            <span class="row-value">{{ order.power_consumed }}</span>
          </div>
        </div>

        <!-- 成交记录 -->
        <section class="card" data-testid="otc-trades">
          <h3 class="card-title">{{ t('page.m_otc_006.trades_title') }}</h3>
          <FiveStateContainer
            :state="otc.tradesLoading ? 'loading' : otc.tradesError ? 'error' : 'default'"
            :error-message="otc.tradesError || ''"
            @retry="otc.fetchTrades"
          >
            <div v-if="trades.length" class="trade-list">
              <div v-for="tr in trades" :key="tr.trade_id" class="trade-row">
                <span class="trade-qty">{{ tr.quantity_apt ?? '-' }}</span>
                <span class="trade-price">{{ tr.price_apt ?? '-' }}</span>
              </div>
            </div>
            <p v-else class="empty" data-testid="trades-empty">{{ t('page.m_otc_006.trades_empty') }}</p>
          </FiveStateContainer>
        </section>

        <!-- APT 流水 / Power Flow Timeline：无冻结端点 → 空态，不伪造 -->
        <section class="card" data-testid="otc-flow">
          <h3 class="card-title">{{ t('page.m_otc_006.flow_title') }}</h3>
          <p class="empty">{{ t('page.m_otc_006.flow_empty') }}</p>
        </section>
      </section>

      <section v-else-if="!otc.detailLoading" class="card" data-testid="otc-not-found">
        <p class="empty">{{ t('page.m_otc_006.not_found') }}</p>
      </section>
    </FiveStateContainer>

    <div class="actions">
      <!-- 取消订单：cancel 写操作 fail-closed → disabled，不开放 -->
      <button class="btn-cancel" disabled data-testid="otc-cancel">
        {{ t('page.m_otc_006.cancel_order') }}
      </button>
      <button class="btn-primary" data-testid="back" @click="router.push('/otc/my')">
        {{ t('page.m_otc_006.primary_action') }}
      </button>
    </div>
  </main>
</template>

<style scoped>
.otc-detail {
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
.hero-id {
  margin: 0;
  font-size: 12px;
  color: var(--gray-500);
  word-break: break-all;
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
  text-align: right;
}
.trade-list {
  display: flex;
  flex-direction: column;
}
.trade-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 44px;
  border-bottom: 1px solid var(--gray-100);
}
.trade-row:last-child {
  border-bottom: none;
}
.trade-qty {
  font-size: 14px;
  font-weight: 700;
}
.trade-price {
  font-size: 14px;
  color: var(--gray-600);
}
.empty {
  margin: 0;
  color: var(--gray-400);
  font-size: 13px;
  text-align: center;
}
.actions {
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
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
.btn-cancel {
  width: 100%;
  height: 48px;
  border: none;
  border-radius: var(--radius-lg);
  background: var(--gray-200);
  color: var(--gray-500);
  font-size: 16px;
  font-weight: 700;
  cursor: not-allowed;
}
</style>
