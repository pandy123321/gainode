<script setup lang="ts">
import { useRouter, useRoute } from 'vue-router'
import { t } from '../i18n'

const router = useRouter()
const route = useRoute()

const tabs = [
  { path: '/home', label: t('home'), icon: 'home' },
  { path: '/robot', label: t('robot'), icon: 'robot' },
  { path: '/team', label: t('team'), icon: 'team' },
  { path: '/my', label: t('my'), icon: 'my' },
]
</script>

<template>
  <nav class="bottom-nav">
    <button
      v-for="tab in tabs"
      :key="tab.path"
      class="nav-item"
      :class="{ active: route.path === tab.path }"
      @click="router.push(tab.path)"
    >
      <svg v-if="tab.icon === 'home'" viewBox="0 0 24 24" fill="none" width="22" height="22">
        <path d="M3 12l9-8 9 8v9a1 1 0 01-1 1h-4v-6h-8v6H4a1 1 0 01-1-1v-9z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
      </svg>
      <svg v-else-if="tab.icon === 'robot'" viewBox="0 0 24 24" fill="none" width="22" height="22">
        <rect x="4" y="8" width="16" height="12" rx="2" stroke="currentColor" stroke-width="1.8"/>
        <circle cx="9" cy="14" r="1.5" fill="currentColor"/>
        <circle cx="15" cy="14" r="1.5" fill="currentColor"/>
        <path d="M12 4v4M10 4h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
      </svg>
      <svg v-else-if="tab.icon === 'team'" viewBox="0 0 24 24" fill="none" width="22" height="22">
        <circle cx="12" cy="8" r="3" stroke="currentColor" stroke-width="1.8"/>
        <path d="M6 20c0-3.3 2.7-6 6-6s6 2.7 6 6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
        <circle cx="18" cy="8" r="2" stroke="currentColor" stroke-width="1.5"/>
        <circle cx="6" cy="8" r="2" stroke="currentColor" stroke-width="1.5"/>
      </svg>
      <svg v-else viewBox="0 0 24 24" fill="none" width="22" height="22">
        <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.8"/>
        <path d="M4 20c0-3.9 3.6-7 8-7s8 3.1 8 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
      </svg>
      <span>{{ tab.label }}</span>
    </button>
  </nav>
</template>

<style scoped lang="scss">
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  z-index: 100;
  display: flex;
  align-items: center;
  justify-content: space-around;
  padding: 8px 0 calc(8px + env(safe-area-inset-bottom));
  background: rgba(14, 22, 32, 0.95);
  backdrop-filter: blur(12px);
  border-top: 1px solid #1E2830;
}

.nav-item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  background: none;
  border: none;
  color: #8A9CB0;
  cursor: pointer;
  padding: 4px 12px;
  transition: color 0.2s;

  span {
    font-size: 10px;
    font-weight: 500;
  }

  &.active {
    color: #3DDC97;
  }
}
</style>
