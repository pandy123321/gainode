<script setup lang="ts">
import { t } from '../i18n'
import FiveStateContainer from '../components/FiveStateContainer.vue'
</script>

<template>
  <main class="root-shell">
    <header class="rs-header">
      <h1>{{ t('app.name') }}</h1>
    </header>
    <FiveStateContainer state="empty" />
  </main>
</template>

<style scoped>
.root-shell {
  min-height: 100vh;
  background: var(--gray-50);
}
.rs-header {
  padding: var(--space-4);
  border-bottom: var(--border-default);
  background: var(--white);
}
.rs-header h1 {
  margin: 0;
  font-size: var(--space-5);
  color: var(--gray-800);
}
</style>
