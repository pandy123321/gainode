<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useSecurityStore } from '../../../stores/security'
import type { SessionDevice } from '../../../api/security'
import { t } from '../../../i18n'
import FiveStateContainer from '../../../components/FiveStateContainer.vue'

const security = useSecurityStore()

const current = computed(() => security.currentSession)
const others = computed(() => security.otherSessions)

/** 脱敏设备指纹：不展示完整 IP / 指纹，只给 os·browser·region */
function deviceLabel(d: SessionDevice): string {
  const parts = [d.os, d.browser, d.location_region].filter(Boolean) as string[]
  return parts.length ? parts.join(' · ') : t('page.m_sec_002.unknown_device')
}

function lastActive(d: SessionDevice): string {
  if (!d.last_active_at) return t('page.m_sec_002.never_active')
  return new Date(d.last_active_at * 1000).toLocaleString()
}

onMounted(() => {
  if (!security.sessions.length) security.fetchSessions()
  if (!security.profile) security.fetchProfile()
})
</script>

<template>
  <main class="sec2-root">
    <header class="page-header">
      <RouterLink to="/security" class="back-link" data-testid="back-link">‹</RouterLink>
      <h1>{{ t('page.m_sec_002.title') }}</h1>
    </header>

    <!-- MFA 注册：写操作 fail-closed，只读展示 enrolled methods -->
    <section class="card" data-testid="mfa-section">
      <h3 class="card-title">{{ t('page.m_sec_002.mfa_title') }}</h3>
      <FiveStateContainer
        :state="security.profileLoading ? 'loading' : security.profileError ? 'error' : 'default'"
        :error-message="security.profileError || ''"
        @retry="security.fetchProfile"
      >
        <p v-if="security.mfaEnrolled.length" class="mfa-methods" data-testid="mfa-methods">
          {{ security.mfaEnrolled.join(' · ') }}
        </p>
        <p v-else class="empty" data-testid="mfa-empty">{{ t('page.m_sec_002.mfa_none') }}</p>
      </FiveStateContainer>
      <button class="action-btn" disabled data-testid="mfa-bind">{{ t('page.m_sec_002.mfa_bind') }}</button>
      <p class="hint">{{ t('page.m_sec_002.mfa_restricted') }}</p>
    </section>

    <!-- 会话 / 设备列表（当前 + 其余可撤销；撤销 fail-closed） -->
    <section class="card" data-testid="sessions-section">
      <h3 class="card-title">{{ t('page.m_sec_002.sessions_title') }}</h3>
      <FiveStateContainer
        :state="security.sessionsLoading ? 'loading' : security.sessionsError ? 'error' : security.sessions.length ? 'default' : 'empty'"
        :error-message="security.sessionsError || ''"
        @retry="security.fetchSessions"
      >
        <div v-if="current" class="device-row" data-testid="current-session">
          <span class="device-badge">{{ t('page.m_sec_002.current') }}</span>
          <div class="device-meta">
            <div class="device-label">{{ deviceLabel(current) }}</div>
            <div class="device-time">{{ lastActive(current) }}</div>
          </div>
        </div>
        <div v-for="d in others" :key="d.session_id" class="device-row" data-testid="other-session">
          <span class="device-badge" :class="{ 'badge-revocable': d.revocable }">
            {{ d.revocable ? t('page.m_sec_002.revocable') : t('page.m_sec_002.not_revocable') }}
          </span>
          <div class="device-meta">
            <div class="device-label">{{ deviceLabel(d) }}</div>
            <div class="device-time">{{ lastActive(d) }}</div>
          </div>
          <button class="revoke-btn" disabled data-testid="revoke-btn">
            {{ t('page.m_sec_002.revoke') }}
          </button>
        </div>
      </FiveStateContainer>
      <p class="hint">{{ t('page.m_sec_002.revoke_restricted') }}</p>
    </section>
  </main>
</template>

<style scoped>
.sec2-root {
  max-width: 560px;
  margin: 0 auto;
  min-height: 100vh;
  background: var(--gray-50);
  color: var(--gray-950);
}
.page-header {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  padding: var(--space-4);
  background: var(--white);
  border-bottom: var(--border-default);
}
.page-header h1 {
  margin: 0;
  font-size: 20px;
  font-weight: 800;
}
.back-link {
  font-size: 22px;
  color: var(--brand-blue-600);
  text-decoration: none;
  min-width: 44px;
  min-height: 44px;
  display: inline-flex;
  align-items: center;
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
.mfa-methods {
  margin: 0;
  font-size: 14px;
  font-weight: 600;
}
.empty {
  margin: 0;
  color: var(--gray-400);
  font-size: 13px;
}
.action-btn {
  width: 100%;
  height: 48px;
  margin-top: var(--space-3);
  border: none;
  border-radius: var(--radius-lg);
  background: var(--brand-blue-600);
  color: var(--white);
  font-size: 16px;
  font-weight: 700;
}
.action-btn:disabled {
  background: var(--gray-200);
  color: var(--gray-500);
  cursor: not-allowed;
}
.hint {
  margin: var(--space-2) 0 0;
  color: var(--gray-400);
  font-size: 12px;
  text-align: center;
  line-height: 1.6;
}
.device-row {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  min-height: 56px;
  border-bottom: 1px solid var(--gray-100);
}
.device-row:last-of-type {
  border-bottom: none;
}
.device-badge {
  flex: 0 0 auto;
  padding: 2px 8px;
  border-radius: var(--radius-md);
  background: var(--gray-100);
  color: var(--gray-600);
  font-size: 11px;
  font-weight: 700;
}
.badge-revocable {
  background: var(--brand-blue-50, #eff6ff);
  color: var(--brand-blue-700);
}
.device-meta {
  flex: 1;
  min-width: 0;
}
.device-label {
  font-size: 14px;
  font-weight: 600;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.device-time {
  font-size: 12px;
  color: var(--gray-400);
}
.revoke-btn {
  flex: 0 0 auto;
  min-height: 36px;
  padding: 0 var(--space-4);
  border: 1px solid var(--gray-200);
  border-radius: var(--radius-md);
  background: var(--white);
  color: var(--gray-500);
  font-size: 13px;
  font-weight: 600;
}
.revoke-btn:disabled {
  background: var(--gray-50);
  color: var(--gray-300);
  cursor: not-allowed;
}
</style>
