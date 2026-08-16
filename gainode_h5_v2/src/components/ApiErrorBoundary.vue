<script setup lang="ts">
import { ref, onErrorCaptured } from 'vue'
import { t } from '../i18n'

const errorMessage = ref<string | null>(null)

onErrorCaptured((err) => {
  errorMessage.value = err instanceof Error ? err.message : String(err)
  return false
})

const emit = defineEmits<{ retry: [] }>()

function retry() {
  errorMessage.value = null
  emit('retry')
}
</script>

<template>
  <div class="api-error-boundary">
    <div v-if="errorMessage" class="aeb-fallback" role="alert">
      <p class="aeb-title">{{ t('common.error') }}</p>
      <p class="aeb-detail">{{ errorMessage }}</p>
      <button class="aeb-retry" @click="retry">{{ t('common.retry') }}</button>
    </div>
    <slot v-else />
  </div>
</template>

<style scoped>
.api-error-boundary {
  width: 100%;
}
.aeb-fallback {
  padding: var(--space-6);
  text-align: center;
  color: var(--gray-700);
}
.aeb-title {
  font-weight: 600;
  color: var(--danger-600);
}
.aeb-detail {
  font-size: var(--space-3);
  color: var(--gray-500);
  word-break: break-word;
}
.aeb-retry {
  margin-top: var(--space-4);
  min-height: 44px;
  padding: 0 var(--space-6);
  border: none;
  border-radius: var(--radius-md);
  background: var(--brand-blue-600);
  color: var(--white);
}
</style>
