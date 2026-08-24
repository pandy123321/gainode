<script setup lang="ts">
import { t } from '../../../i18n'
import FiveStateContainer from '../../../components/FiveStateContainer.vue'
import DataStateBadge from '../../../components/DataStateBadge.vue'
</script>

<template>
  <main class="support-root">
    <header class="page-header">
      <h1>{{ t('page.m_support_001.title') }}</h1>
      <DataStateBadge page-id="M-SUPPORT-001" />
      <p class="page-desc">{{ t('page.m_support_001.description') }}</p>
    </header>

    <!-- 创建工单：POST /me/tickets 无端点 → fail-closed，禁用 -->
    <section class="card" data-testid="create">
      <button class="create-btn" disabled data-testid="create-btn">
        {{ t('page.m_support_001.create_action') }}
      </button>
      <p class="hint">{{ t('page.m_support_001.create_restricted') }}</p>
    </section>

    <!-- 常见问题：GET /help（FAQ config）无端点 → 受限 -->
    <section class="card" data-testid="faq">
      <h3 class="card-title">{{ t('page.m_support_001.faq_title') }}</h3>
      <FiveStateContainer
        state="restricted"
        :restricted-message="t('page.m_support_001.faq_restricted')"
      />
    </section>

    <!-- 我的工单：GET /me/tickets 无端点 → 受限 -->
    <section class="card" data-testid="tickets">
      <h3 class="card-title">{{ t('page.m_support_001.tickets_title') }}</h3>
      <FiveStateContainer
        state="restricted"
        :restricted-message="t('page.m_support_001.tickets_restricted')"
      />
    </section>
  </main>
</template>

<style scoped>
.support-root {
  max-width: 560px;
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
.page-desc {
  margin: var(--space-2) 0 0;
  color: var(--gray-500);
  font-size: 13px;
}
.card {
  margin: var(--space-3) var(--space-4) 0;
  padding: var(--space-4);
  background: var(--white);
  border: var(--border-default);
  border-radius: var(--radius-lg);
}
.card-title {
  margin: 0 0 var(--space-2);
  font-size: 15px;
  font-weight: 700;
  color: var(--gray-800);
}
.create-btn {
  width: 100%;
  height: 48px;
  border: none;
  border-radius: var(--radius-lg);
  background: var(--brand-blue-600);
  color: var(--white);
  font-size: 16px;
  font-weight: 700;
}
.create-btn:disabled {
  background: var(--gray-200);
  color: var(--gray-500);
  cursor: not-allowed;
}
.hint {
  margin: var(--space-2) 0 0;
  color: var(--gray-400);
  font-size: 12px;
  text-align: center;
}
</style>
