<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useOtcStore } from '../../../stores/otc'
import { t } from '../../../i18n'
import FiveStateContainer from '../../../components/FiveStateContainer.vue'
import type { OtcOrder } from '../../../api/otc'
import DataStateBadge from '../../../components/DataStateBadge.vue'

const router = useRouter()
const otc = useOtcStore()

const orders = computed(() => otc.orders)

function sideLabel(o: OtcOrder) {
  return t(`otc.side.${o.side}`)
}
function statusLabel(o: OtcOrder) {
  return t(`otc.status.${o.status}`)
}

onMounted(() => {
  if (!otc.orders.length) otc.fetchMyOrders()
})
</script>

<template>
  <main class="otc-orders">
    <header class="page-header">
      <h1>{{ t('page.m_otc_005.title') }}</h1>
      <DataStateBadge page-id="M-OTC-005" />
    </header>

    <FiveStateContainer
      :state="otc.ordersLoading ? 'loading' : otc.ordersError ? 'error' : 'default'"
      :error-message="otc.ordersError || ''"
      @retry="otc.fetchMyOrders"
    >
      <div v-if="orders.length" class="list" data-testid="otc-order-list">
        <button
          v-for="o in orders"
          :key="o.otc_order_id"
          class="order-row"
          data-testid="otc-order-row"
          @click="router.push(`/otc/${o.otc_order_id}`)"
        >
          <span class="row-left">
            <span class="row-side" :data-side="o.side">{{ sideLabel(o) }}</span>
            <span class="row-status" :data-status="o.status">{{ statusLabel(o) }}</span>
          </span>
          <span class="row-right">
            <span class="row-qty">{{ o.quantity_apt ?? '-' }}</span>
            <span v-if="o.filled_quantity_apt" class="row-progress">
              {{ o.filled_quantity_apt }} / {{ o.remaining_quantity_apt ?? '-' }}
            </span>
          </span>
        </button>
      </div>
      <p v-else class="empty" data-testid="otc-orders-empty">{{ t('page.m_otc_005.empty') }}</p>
    </FiveStateContainer>
  </main>
</template>

<style scoped>
.otc-orders {
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
.list {
  margin: var(--space-3) var(--space-4) 0;
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}
.order-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 68px;
  padding: var(--space-3) var(--space-4);
  text-align: left;
  background: var(--white);
  border: var(--border-default);
  border-radius: var(--radius-lg);
  cursor: pointer;
}
.row-left {
  display: flex;
  flex-direction: column;
  gap: var(--space-1);
}
.row-side {
  font-size: 15px;
  font-weight: 800;
}
.row-side[data-side='BUY'] {
  color: var(--brand-blue-700);
}
.row-side[data-side='SELL'] {
  color: var(--gray-600);
}
.row-status {
  padding: 1px 8px;
  font-size: 11px;
  border-radius: 999px;
  background: var(--gray-100);
  color: var(--gray-600);
}
.row-status[data-status='partial'] {
  background: var(--brand-blue-50);
  color: var(--brand-blue-700);
}
.row-status[data-status='cancelled'],
.row-status[data-status='expired'],
.row-status[data-status='rejected'] {
  background: var(--gray-100);
  color: var(--gray-500);
}
.row-status[data-status='disputed'] {
  background: var(--warning-50);
  color: var(--warning-700);
}
.row-right {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: var(--space-1);
}
.row-qty {
  font-size: 16px;
  font-weight: 800;
}
.row-progress {
  font-size: 12px;
  color: var(--gray-400);
}
.empty {
  margin: var(--space-6) 0 0;
  color: var(--gray-400);
  font-size: 13px;
  text-align: center;
}
</style>
