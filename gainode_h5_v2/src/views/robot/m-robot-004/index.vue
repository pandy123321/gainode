<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useRobotStore } from '../../../stores/robot'
import { t } from '../../../i18n'
import FiveStateContainer from '../../../components/FiveStateContainer.vue'

const route = useRoute()
const router = useRouter()
const robot = useRobotStore()

const orderId = computed(() => String(route.params.id ?? ''))

const statusLabel = computed(() => {
  const s = robot.upgradeOrder?.status ?? 'pending'
  return t(`robot.upgrade_status.${s}`)
})

function reload() {
  if (orderId.value) robot.fetchUpgradeOrder(orderId.value)
}

onMounted(reload)
</script>

<template>
  <main class="upgrade-result">
    <header class="page-header">
      <h1>{{ t('page.m_robot_004.title') }}</h1>
    </header>

    <FiveStateContainer
      :state="robot.upgradeOrderLoading ? 'loading' : robot.upgradeOrderError ? 'error' : 'default'"
      :error-message="robot.upgradeOrderError || ''"
      @retry="reload"
    >
      <section v-if="robot.upgradeOrder" class="result" data-testid="upgrade-result">
        <div class="result-hero" :data-status="robot.upgradeOrder.status">
          <p class="result-kicker">{{ t('page.m_robot_004.description') }}</p>
          <h2 class="result-status">{{ statusLabel }}</h2>
        </div>

        <div class="card">
          <div class="row">
            <span class="row-label">{{ t('page.m_robot_004.from_level') }}</span>
            <span class="row-value">Lv.{{ robot.upgradeOrder.from_level }}</span>
          </div>
          <div class="row">
            <span class="row-label">{{ t('page.m_robot_004.to_level') }}</span>
            <span class="row-value">Lv.{{ robot.upgradeOrder.to_level }}</span>
          </div>
          <div v-if="robot.upgradeOrder.apt_cost != null" class="row">
            <span class="row-label">{{ t('page.m_robot_004.apt_cost') }}</span>
            <span class="row-value">{{ robot.upgradeOrder.apt_cost }}</span>
          </div>
          <div v-if="robot.upgradeOrder.power_cap_after != null" class="row">
            <span class="row-label">{{ t('page.m_robot_004.power_cap_after') }}</span>
            <span class="row-value">{{ robot.upgradeOrder.power_cap_after }}</span>
          </div>
          <div class="row">
            <span class="row-label">{{ t('page.m_robot_004.order_id') }}</span>
            <span class="row-value mono">{{ robot.upgradeOrder.upgrade_order_id }}</span>
          </div>
          <div v-if="robot.upgradeOrder.rule_version" class="row">
            <span class="row-label">{{ t('page.m_robot_004.rule_version') }}</span>
            <span class="row-value mono">{{ robot.upgradeOrder.rule_version }}</span>
          </div>
        </div>
      </section>
    </FiveStateContainer>

    <div class="actions">
      <button class="btn-primary" data-testid="back-robot" @click="router.push('/robot')">
        {{ t('page.m_robot_004.primary_action') }}
      </button>
    </div>
  </main>
</template>

<style scoped>
.upgrade-result {
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
