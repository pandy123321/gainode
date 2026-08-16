<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { t, getCurrentLanguage, setLanguage, getSupportedLanguages } from '../../i18n'

const router = useRouter()
const languages = getSupportedLanguages()

const currentLang = computed(() => getCurrentLanguage())

function select(lang: string) {
  setLanguage(lang)
  router.back()
}
</script>

<template>
  <div class="lang-screen">
    <header class="screen-header">
      <button class="back-btn" @click="router.back()">
        <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
      <h1>{{ t('lang_title') }}</h1>
    </header>

    <div class="lang-list">
      <button
        v-for="lang in languages"
        :key="lang.code"
        class="lang-item"
        :class="{ active: currentLang === lang.code }"
        @click="select(lang.code)"
      >
        <div class="lang-info">
          <span class="lang-name">{{ lang.nativeName }}</span>
          <span class="lang-label">{{ lang.name }}</span>
        </div>
        <svg v-if="currentLang === lang.code" viewBox="0 0 24 24" fill="none" width="20" height="20" class="check-icon"><path d="M5 12l5 5L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
    </div>
  </div>
</template>

<style scoped lang="scss">
$elevated: #0E1620;
$border: #1E2830;
$green: #3DDC97;
$muted: #8A9CB0;

.lang-screen { min-height: 100vh; background: #0A0E14; }

.screen-header {
  position: sticky; top: 0; z-index: 50;
  display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: rgba(14, 22, 32, 0.95); backdrop-filter: blur(10px);
  .back-btn { background: none; border: none; color: white; cursor: pointer; }
  h1 { font-size: 18px; font-weight: 700; color: white; margin: 0; }
}

.lang-list { padding: 12px 16px; }

.lang-item {
  display: flex; align-items: center; justify-content: space-between;
  width: 100%; padding: 16px; background: $elevated; border: 1px solid $border;
  border-radius: 12px; margin-bottom: 8px; cursor: pointer; color: white;
  transition: border-color 0.2s;

  &.active {
    border-color: $green;
    .lang-name { color: $green; }
  }

  .lang-info {
    text-align: left;
    .lang-name { display: block; font-size: 16px; font-weight: 600; }
    .lang-label { font-size: 12px; color: $muted; }
  }

  .check-icon { color: $green; flex-shrink: 0; }
}
</style>
