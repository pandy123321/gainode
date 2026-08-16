<script setup lang="ts">
import { t } from '../i18n'

export type FiveState = 'default' | 'loading' | 'empty' | 'error' | 'restricted'

withDefaults(
  defineProps<{
    state: FiveState
    errorMessage?: string
    restrictedMessage?: string
  }>(),
  {
    state: 'default',
    errorMessage: '',
    restrictedMessage: '',
  },
)

defineEmits<{ retry: [] }>()
</script>

<template>
  <div class="five-state">
    <div v-if="state === 'loading'" class="fs-body" data-testid="fs-loading">
      <span class="fs-spinner" aria-hidden="true" />
      <span>{{ t('common.loading') }}</span>
    </div>
    <div v-else-if="state === 'empty'" class="fs-body" data-testid="fs-empty">
      <span>{{ t('common.empty') }}</span>
    </div>
    <div v-else-if="state === 'error'" class="fs-body" data-testid="fs-error">
      <span>{{ errorMessage || t('common.error') }}</span>
      <button class="fs-retry" @click="$emit('retry')">{{ t('common.retry') }}</button>
    </div>
    <div v-else-if="state === 'restricted'" class="fs-body" data-testid="fs-restricted">
      <span>{{ restrictedMessage || t('common.restricted') }}</span>
    </div>
    <slot v-else />
  </div>
</template>

<style scoped>
.five-state {
  width: 100%;
}
.fs-body {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-8) var(--space-4);
  color: var(--gray-500);
  text-align: center;
}
.fs-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid var(--gray-200);
  border-top-color: var(--brand-blue-600);
  border-radius: 50%;
  animation: fs-spin 0.8s linear infinite;
}
@keyframes fs-spin {
  to {
    transform: rotate(360deg);
  }
}
.fs-retry {
  min-height: 44px;
  padding: 0 var(--space-6);
  border: none;
  border-radius: var(--radius-md);
  background: var(--brand-blue-600);
  color: var(--white);
}
@media (prefers-reduced-motion: reduce) {
  .fs-spinner {
    animation: none;
  }
}
</style>
