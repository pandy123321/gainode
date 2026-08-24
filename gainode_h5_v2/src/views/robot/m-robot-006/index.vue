<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRobotStore } from '../../../stores/robot'
import { t } from '../../../i18n'
import FiveStateContainer from '../../../components/FiveStateContainer.vue'
import type { AIReward } from '../../../api/robot'
import DataStateBadge from '../../../components/DataStateBadge.vue'

const robot = useRobotStore()

const claimable = computed<AIReward[]>(() => robot.claimableRewards)

const rewardStateLabel = (state: AIReward['state']) => t(`robot.reward_state.${state}`)

function reload() {
  robot.fetchRewards()
}

onMounted(reload)
</script>

<template>
  <main class="rewards">
    <header class="page-header">
      <h1>{{ t('page.m_robot_006.title') }}</h1>
      <DataStateBadge page-id="M-ROBOT-006" />
    </header>

    <FiveStateContainer
      :state="robot.rewardsLoading ? 'loading' : robot.rewardsError ? 'error' : 'default'"
      :error-message="robot.rewardsError || ''"
      @retry="reload"
    >
      <template v-if="!robot.rewards.length">
        <section class="card" data-testid="rewards-empty">
          <p class="empty">{{ t('page.m_robot_006.empty') }}</p>
        </section>
      </template>

      <template v-else>
        <section class="summary" data-testid="rewards-summary">
          <span class="summary-label">{{ t('page.m_robot_006.claimable') }}</span>
          <span class="summary-value">{{ claimable.length }}</span>
        </section>

        <!-- Claim 提交 fail-closed：显示 restricted，不开放真实领取 -->
        <section class="claim" data-testid="claim-restricted">
          <p class="claim-hint">{{ t('page.m_robot_006.claim_restricted') }}</p>
          <button class="btn-primary" disabled>{{ t('page.m_robot_006.primary_action') }}</button>
        </section>

        <section class="card" data-testid="rewards-list">
          <h3 class="card-title">{{ t('page.m_robot_006.history_title') }}</h3>
          <div v-for="r in robot.rewards" :key="r.reward_id" class="reward-row">
            <div class="reward-main">
              <span class="reward-amount">{{ r.quantity_apt ?? '-' }} APT</span>
              <span class="reward-meta">{{ t('page.m_robot_006.period') }} {{ r.period ?? '-' }}</span>
            </div>
            <span class="reward-state">{{ rewardStateLabel(r.state) }}</span>
          </div>
        </section>
      </template>
    </FiveStateContainer>
  </main>
</template>

<style scoped>
.rewards {
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
.summary {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin: var(--space-3) var(--space-4) 0;
  padding: var(--space-4);
  background: var(--white);
  border: var(--border-default);
  border-radius: var(--radius-lg);
}
.summary-label {
  color: var(--gray-500);
  font-size: 14px;
}
.summary-value {
  font-size: 22px;
  font-weight: 800;
  color: var(--gray-900);
}
.claim {
  margin: var(--space-3) var(--space-4) 0;
  padding: var(--space-4);
  background: var(--gray-100);
  border: var(--border-default);
  border-radius: var(--radius-lg);
}
.claim-hint {
  margin: 0 0 var(--space-3);
  color: var(--gray-500);
  font-size: 13px;
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
}
.btn-primary:disabled {
  background: var(--gray-300);
  color: var(--gray-500);
  cursor: not-allowed;
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
.reward-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 56px;
  border-bottom: 1px solid var(--gray-100);
}
.reward-row:last-child {
  border-bottom: none;
}
.reward-main {
  display: flex;
  flex-direction: column;
}
.reward-amount {
  font-weight: 700;
  font-size: 15px;
}
.reward-meta {
  color: var(--gray-400);
  font-size: 12px;
}
.reward-state {
  font-size: 13px;
  color: var(--gray-600);
}
.empty {
  margin: 0;
  color: var(--gray-400);
  font-size: 13px;
  text-align: center;
}
</style>
