<script setup lang="ts">
import { useRouter } from 'vue-router'
import { t } from '../../../i18n'
import FiveStateContainer from '../../../components/FiveStateContainer.vue'
import DataStateBadge from '../../../components/DataStateBadge.vue'

const router = useRouter()
</script>

<template>
  <main class="restricted-page">
    <header class="page-header">
      <h1>{{ t('page.m_support_002.title') }}</h1>
      <DataStateBadge page-id="M-SUPPORT-002" />
    </header>
    <FiveStateContainer state="restricted" :restricted-message="t('page.m_support_002.restricted')">
      <p class="hint">{{ t('page.m_support_002.description') }}</p>
    </FiveStateContainer>
    <div class="actions">
      <button class="btn-primary" data-testid="back" @click="router.push('/support')">
        {{ t('common.cancel') }}
      </button>
    </div>
  </main>
</template>

<style scoped>
.restricted-page {
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
.hint {
  margin: 0;
  color: var(--gray-500);
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
