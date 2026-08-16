<script setup lang="ts">
import { t } from '../i18n'

withDefaults(
  defineProps<{
    /** 原请求 Idempotency-Key（用于查询原请求终态） */
    idempotencyKey?: string
  }>(),
  {
    idempotencyKey: '',
  },
)

defineEmits<{ query: [] }>()
</script>

<template>
  <div class="unknown-result" data-testid="unknown-result">
    <p class="ur-title">{{ t('common.unknownResult') }}</p>
    <p class="ur-hint">Idempotency-Key: {{ idempotencyKey }}</p>
    <button class="ur-query" @click="$emit('query')">{{ t('common.confirm') }}</button>
  </div>
</template>

<style scoped>
.unknown-result {
  padding: var(--space-8) var(--space-4);
  text-align: center;
}
.ur-title {
  margin: 0 0 var(--space-2);
  font-weight: 600;
  color: var(--warning-600);
}
.ur-hint {
  color: var(--gray-500);
  font-size: var(--space-3);
  word-break: break-all;
}
.ur-query {
  margin-top: var(--space-4);
  min-height: 44px;
  padding: 0 var(--space-6);
  border: none;
  border-radius: var(--radius-md);
  background: var(--brand-blue-600);
  color: var(--white);
}
</style>
