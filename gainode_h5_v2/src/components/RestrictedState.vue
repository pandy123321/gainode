<script setup lang="ts">
import { computed } from 'vue'
import { t } from '../i18n'

const props = withDefaults(
  defineProps<{
    /** 原因分类（AUTH_UNAUTHENTICATED / FEATURE_CLOSED / POLICY_DENIED / KYC_REQUIRED 等） */
    reason?: string
    /** 恢复/补件/申诉/等待中的下一步说明（可选） */
    nextStep?: string
  }>(),
  {
    reason: '',
    nextStep: '',
  },
)

const title = computed(() => t('common.restricted'))
</script>

<template>
  <div class="restricted-state" data-testid="restricted-state">
    <h2 class="rs-title">{{ title }}</h2>
    <p class="rs-reason">{{ reason || t('common.restricted') }}</p>
    <p v-if="nextStep" class="rs-next">{{ nextStep }}</p>
  </div>
</template>

<style scoped>
.restricted-state {
  padding: var(--space-8) var(--space-4);
  text-align: center;
}
.rs-title {
  margin: 0 0 var(--space-3);
  font-size: var(--space-5);
  color: var(--gray-800);
}
.rs-reason {
  color: var(--gray-600);
}
.rs-next {
  color: var(--gray-500);
  font-size: var(--space-3);
}
</style>
