<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useSessionStore } from '../../../stores/session'
import { authApi } from '../../../api/auth'
import { t, setLanguage, getCurrentLanguage, getSupportedLanguages } from '../../../i18n'
import FiveStateContainer from '../../../components/FiveStateContainer.vue'
import DataStateBadge from '../../../components/DataStateBadge.vue'

const router = useRouter()
const session = useSessionStore()

const languages = getSupportedLanguages()
const currentLang = ref(getCurrentLanguage())
const loggingOut = ref(false)
const logoutError = ref('')

function pickLanguage(code: string) {
  setLanguage(code)
  currentLang.value = code
}

async function logout() {
  if (loggingOut.value) return
  loggingOut.value = true
  logoutError.value = ''
  try {
    // best-effort 服务端注销；失败仍本地清 token（不得把用户锁在会话里）
    try {
      await authApi.logout()
    } catch {
      /* 忽略：网络失败不应阻断本地退出 */
    }
  } finally {
    session.clear()
    loggingOut.value = false
    router.replace('/auth/login')
  }
}
</script>

<template>
  <main class="settings-root">
    <header class="page-header">
      <h1>{{ t('page.m_settings_001.title') }}</h1>
      <DataStateBadge page-id="M-SETTINGS-001" />
    </header>

    <!-- 语言：本地切换（保留上下文；不改变业务数值/规则语义） -->
    <section class="card" data-testid="settings-language">
      <h3 class="card-title">{{ t('page.m_settings_001.language_title') }}</h3>
      <div v-for="lang in languages" :key="lang.code" class="lang-row">
        <button
          class="lang-option"
          :class="{ 'lang-active': currentLang === lang.code }"
          :data-testid="'lang-' + lang.code"
          @click="pickLanguage(lang.code)"
        >
          <span class="lang-native">{{ lang.nativeName }}</span>
          <span class="lang-name">{{ lang.name }}</span>
          <span v-if="currentLang === lang.code" class="lang-check" aria-label="selected">✓</span>
        </button>
      </div>
    </section>

    <!-- 时区 / 通知偏好：GET/PUT /me/preferences 未冻结 → 受限 -->
    <section class="card" data-testid="settings-preferences">
      <h3 class="card-title">{{ t('page.m_settings_001.preferences_title') }}</h3>
      <FiveStateContainer
        state="restricted"
        :restricted-message="t('page.m_settings_001.preferences_restricted')"
      />
    </section>

    <!-- 法律 / 帮助 -->
    <section class="card" data-testid="settings-legal">
      <RouterLink to="/notices" class="row-link" data-testid="legal-notices">
        <span class="row-label">{{ t('page.m_settings_001.legal_notices') }}</span>
        <span class="entry-arrow" aria-hidden="true">›</span>
      </RouterLink>
    </section>

    <!-- 退出登录 -->
    <section class="card" data-testid="settings-logout">
      <button class="logout-btn" :disabled="loggingOut" data-testid="logout-btn" @click="logout">
        {{ loggingOut ? t('common.loading') : t('page.m_settings_001.logout') }}
      </button>
      <p v-if="logoutError" class="hint" data-testid="logout-error">{{ logoutError }}</p>
    </section>
  </main>
</template>

<style scoped>
.settings-root {
  max-width: 560px;
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
.card {
  margin: var(--space-3) var(--space-4) 0;
  padding: var(--space-4);
  background: var(--white);
  border: var(--border-default);
  border-radius: var(--radius-lg);
}
.card-title {
  margin: 0 0 var(--space-2);
  font-size: 15px;
  font-weight: 700;
  color: var(--gray-800);
}
.lang-row {
  border-bottom: 1px solid var(--gray-100);
}
.lang-row:last-child {
  border-bottom: none;
}
.lang-option {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  width: 100%;
  min-height: 48px;
  padding: 0;
  border: none;
  background: none;
  text-align: left;
  cursor: pointer;
}
.lang-native {
  font-size: 15px;
  font-weight: 600;
}
.lang-name {
  flex: 1;
  color: var(--gray-500);
  font-size: 13px;
}
.lang-check {
  color: var(--brand-blue-600);
  font-weight: 700;
}
.lang-active .lang-native {
  color: var(--brand-blue-600);
}
.row-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 48px;
  text-decoration: none;
  color: inherit;
}
.row-label {
  color: var(--gray-600);
  font-size: 14px;
  font-weight: 600;
}
.entry-arrow {
  color: var(--gray-300);
  font-size: 20px;
}
.logout-btn {
  width: 100%;
  height: 48px;
  border: none;
  border-radius: var(--radius-lg);
  background: var(--gray-800);
  color: var(--white);
  font-size: 16px;
  font-weight: 700;
}
.logout-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.hint {
  margin: var(--space-2) 0 0;
  color: var(--gray-400);
  font-size: 12px;
  text-align: center;
}
</style>
