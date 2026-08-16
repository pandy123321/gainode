<script setup lang="ts">
import { t } from '../i18n'

export interface NavItem {
  key: string
  label: string
  to: string
}

withDefaults(defineProps<{ active?: string }>(), { active: 'home' })
</script>

<template>
  <nav class="bottom-nav" aria-label="主导航">
    <RouterLink
      v-for="item in [
        { key: 'home', label: t('nav.home'), to: '/' },
        { key: 'robot', label: t('nav.robot'), to: '/robot' },
        { key: 'prediction', label: t('nav.prediction'), to: '/prediction' },
        { key: 'me', label: t('nav.me'), to: '/me' },
      ]"
      :key="item.key"
      :to="item.to"
      class="bn-item"
      :class="{ 'bn-active': active === item.key }"
      :aria-current="active === item.key ? 'page' : undefined"
      :data-testid="'nav-' + item.key"
    >
      <span class="bn-label">{{ item.label }}</span>
    </RouterLink>
  </nav>
</template>

<style scoped>
.bottom-nav {
  position: sticky;
  bottom: 0;
  display: flex;
  height: 64px;
  padding-bottom: env(safe-area-inset-bottom);
  background: var(--white);
  border-top: var(--border-default);
  z-index: 10;
}
.bn-item {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 64px;
  text-decoration: none;
  color: var(--gray-500);
}
.bn-label {
  font-size: 13px;
  font-weight: 600;
}
.bn-active {
  color: var(--brand-blue-600);
}
</style>
