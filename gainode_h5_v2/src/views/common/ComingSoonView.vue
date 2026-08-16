<script setup lang="ts">
import { t } from '../../i18n'
import BottomNav from '../../components/BottomNav.vue'

defineProps<{ pageTitle?: string; navActive?: string }>()
</script>

<template>
  <main class="coming-soon">
    <header class="cs-header">
      <h1>{{ pageTitle || t('common.comingSoon') }}</h1>
    </header>
    <div class="cs-body" data-testid="coming-soon">
      <p class="cs-hint">{{ t('common.comingSoon') }}</p>
    </div>
    <BottomNav :active="navActive" />
  </main>
</template>

<style scoped>
.coming-soon {
  min-height: 100vh;
  background: var(--gray-50);
}
.cs-header {
  padding: var(--space-4);
  border-bottom: var(--border-default);
  background: var(--white);
}
.cs-header h1 {
  margin: 0;
  font-size: var(--space-5);
  color: var(--gray-800);
}
.cs-body {
  padding: var(--space-12) var(--space-4);
  text-align: center;
}
.cs-hint {
  margin: 0;
  color: var(--gray-400);
  font-size: 14px;
}
</style>
