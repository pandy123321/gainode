<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { usePowerStore } from '../../../stores/power'
import { t } from '../../../i18n'
import FiveStateContainer from '../../../components/FiveStateContainer.vue'
import DataStateBadge from '../../../components/DataStateBadge.vue'

const power = usePowerStore()

const position = computed(() => power.position)
const ratio = computed(() => power.ratio)

function fmtTime(ts?: number) {
  if (ts == null) return '-'
  return new Date(ts * 1000).toLocaleString()
}

onMounted(() => {
  if (!power.hasPosition) power.fetch()
})
</script>

<template>
  <main class="power-root">
    <header class="page-header">
      <h1>{{ t('page.m_power_001.title') }}</h1>
      <DataStateBadge page-id="M-POWER-001" />
    </header>

    <FiveStateContainer
      :state="power.loading ? 'loading' : power.error ? 'error' : 'default'"
      :error-message="power.error || ''"
      @retry="power.fetch"
    >
      <template v-if="position">
        <!-- Power Battery 首屏主视觉 -->
        <section class="hero" data-testid="power-battery">
          <p class="hero-kicker">{{ t('page.m_power_001.description') }}</p>
          <div class="battery">
            <div class="battery-fill" :style="{ width: ratio != null ? `${ratio * 100}%` : '0%' }" />
          </div>
          <h2 class="hero-value">{{ position.available }}</h2>
          <p class="hero-unit">{{ t('page.m_power_001.available') }} / {{ position.limit ?? '-' }} {{ t('power.cap') }}</p>
        </section>

        <!-- 状态拆分 -->
        <section class="card" data-testid="power-breakdown">
          <div class="row">
            <span class="row-label">{{ t('power.available') }}</span>
            <span class="row-value">{{ position.available }}</span>
          </div>
          <div class="row">
            <span class="row-label">{{ t('power.frozen') }}</span>
            <span class="row-value">{{ position.frozen ?? '0' }}</span>
          </div>
          <div class="row">
            <span class="row-label">{{ t('power.consumed') }}</span>
            <span class="row-value">{{ position.consumed_period ?? '0' }}</span>
          </div>
          <div class="row">
            <span class="row-label">{{ t('power.released') }}</span>
            <span class="row-value">{{ position.released_period ?? '0' }}</span>
          </div>
          <div class="row">
            <span class="row-label">{{ t('power.recovering') }}</span>
            <span class="row-value">{{ position.recovering ?? '0' }}</span>
          </div>
          <div class="row">
            <span class="row-label">{{ t('power.cap') }}</span>
            <span class="row-value">{{ position.limit ?? '-' }}</span>
          </div>
          <div v-if="position.power_cap_source_robot_level != null" class="row">
            <span class="row-label">{{ t('power.cap_source_robot_level') }}</span>
            <span class="row-value">Lv.{{ position.power_cap_source_robot_level }}</span>
          </div>
        </section>

        <!-- 恢复时间 -->
        <section class="card" data-testid="power-restore">
          <div v-if="position.next_restore_at != null" class="row">
            <span class="row-label">{{ t('power.next_restore_at') }}</span>
            <span class="row-value">{{ fmtTime(position.next_restore_at) }}</span>
          </div>
          <div v-if="position.last_restore_at != null" class="row">
            <span class="row-label">{{ t('power.last_restore_at') }}</span>
            <span class="row-value">{{ fmtTime(position.last_restore_at) }}</span>
          </div>
        </section>
      </template>
    </FiveStateContainer>

    <!-- 最近 7 日变化：Power Ledger 无冻结 DTO/路径 → 不伪造 -->
    <section class="card" data-testid="power-trend">
      <h3 class="card-title">{{ t('page.m_power_001.trend_title') }}</h3>
      <p class="empty">{{ t('page.m_power_001.trend_empty') }}</p>
    </section>

    <!-- Power 使用场景：只讲规则，不给数值 -->
    <section class="card" data-testid="power-usage">
      <h3 class="card-title">{{ t('page.m_power_001.usage_title') }}</h3>
      <p class="hint">{{ t('page.m_power_001.usage_desc') }}</p>
      <ul class="usage-list">
        <li>{{ t('page.m_power_001.usage_withdrawal') }}</li>
        <li>{{ t('page.m_power_001.usage_robot_start') }}</li>
        <li>{{ t('page.m_power_001.usage_otc_sell') }}</li>
      </ul>
    </section>

    <!-- 冻结 / 关联操作：无 related_actions 下发 → 空态 -->
    <section class="card" data-testid="power-frozen-actions">
      <h3 class="card-title">{{ t('page.m_power_001.frozen_actions_title') }}</h3>
      <p class="empty">{{ t('page.m_power_001.frozen_actions_empty') }}</p>
    </section>

    <!-- OTC 快捷动作（fail-closed） -->
    <section class="card" data-testid="power-otc">
      <div class="otc-grid">
        <button class="otc-btn" disabled data-testid="otc-buy">{{ t('page.m_asset_001.otc_buy') }}</button>
        <button class="otc-btn" disabled data-testid="otc-sell">{{ t('page.m_asset_001.otc_sell') }}</button>
      </div>
      <p class="hint">{{ t('page.m_asset_001.otc_restricted') }}</p>
    </section>
  </main>
</template>

<style scoped>
.power-root {
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
  text-align: center;
}
.hero-kicker {
  margin: 0 0 var(--space-3);
  color: var(--gray-500);
  font-size: 13px;
}
.battery {
  height: 24px;
  margin: 0 auto var(--space-3);
  max-width: 280px;
  background: var(--gray-100);
  border-radius: 999px;
  overflow: hidden;
}
.battery-fill {
  height: 100%;
  background: var(--brand-blue-600);
  border-radius: 999px;
  transition: width 0.3s ease;
}
.hero-value {
  margin: 0;
  font-size: 32px;
  font-weight: 800;
}
.hero-unit {
  margin: var(--space-1) 0 0;
  font-size: 13px;
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
.hint {
  margin: 0;
  color: var(--gray-500);
  font-size: 13px;
  line-height: 1.6;
}
.usage-list {
  margin: var(--space-2) 0 0;
  padding-left: var(--space-4);
  color: var(--gray-700);
  font-size: 14px;
  line-height: 1.9;
}
.empty {
  margin: 0;
  color: var(--gray-400);
  font-size: 13px;
  text-align: center;
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
</style>
