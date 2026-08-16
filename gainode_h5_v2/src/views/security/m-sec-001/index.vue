<script setup lang="ts">
import { onMounted } from 'vue'
import { useSecurityStore } from '../../../stores/security'
import { t } from '../../../i18n'
import FiveStateContainer from '../../../components/FiveStateContainer.vue'

const security = useSecurityStore()

onMounted(() => {
  if (!security.profile) security.fetchProfile()
  if (!security.sessions.length) security.fetchSessions()
})
</script>

<template>
  <main class="sec-root">
    <header class="page-header">
      <h1>{{ t('page.m_sec_001.title') }}</h1>
      <RouterLink to="/security/sessions" class="header-link" data-testid="manage-link">
        {{ t('page.m_sec_001.manage') }}
      </RouterLink>
    </header>

    <!-- 安全状态总览（克制，不靠大面积红色制造恐慌） -->
    <section class="card" data-testid="sec-summary">
      <FiveStateContainer
        :state="security.profileLoading ? 'loading' : security.profileError ? 'error' : 'default'"
        :error-message="security.profileError || ''"
        @retry="security.fetchProfile"
      >
        <div class="row">
          <span class="row-label">{{ t('page.m_sec_001.mfa_methods') }}</span>
          <span class="row-value" data-testid="sec-mfa-count">
            {{ security.mfaEnrolled.length }}
          </span>
        </div>
        <div class="row">
          <span class="row-label">{{ t('page.m_sec_001.active_sessions') }}</span>
          <span class="row-value" data-testid="sec-session-count">
            {{ security.sessions.length }}
          </span>
        </div>
        <div class="row" v-if="security.suspiciousCount > 0">
          <span class="row-label">{{ t('page.m_sec_001.suspicious_flags') }}</span>
          <span class="row-value row-warn" data-testid="sec-suspicious-count">
            {{ security.suspiciousCount }}
          </span>
        </div>
      </FiveStateContainer>
    </section>

    <!-- MFA 分组 -->
    <section class="card" data-testid="sec-mfa">
      <h3 class="card-title">{{ t('page.m_sec_001.mfa_title') }}</h3>
      <RouterLink to="/security/sessions" class="row-link" data-testid="sec-mfa-entry">
        <span class="row-label">{{ t('page.m_sec_001.mfa_manage') }}</span>
        <span class="entry-arrow" aria-hidden="true">›</span>
      </RouterLink>
    </section>

    <!-- 设备 / Session 分组 -->
    <section class="card" data-testid="sec-devices">
      <h3 class="card-title">{{ t('page.m_sec_001.devices_title') }}</h3>
      <RouterLink to="/security/sessions" class="row-link" data-testid="sec-devices-entry">
        <span class="row-label">{{ t('page.m_sec_001.devices_manage') }}</span>
        <span class="entry-arrow" aria-hidden="true">›</span>
      </RouterLink>
    </section>

    <!-- 登录记录：source 未裁决 → UNAVAILABLE（fail-closed，受限展示） -->
    <section class="card" data-testid="sec-audit">
      <h3 class="card-title">{{ t('page.m_sec_001.audit_title') }}</h3>
      <FiveStateContainer state="restricted" :restricted-message="t('page.m_sec_001.audit_restricted')" />
    </section>

    <!-- 密码安全 -->
    <section class="card" data-testid="sec-password">
      <h3 class="card-title">{{ t('page.m_sec_001.password_title') }}</h3>
      <RouterLink to="/auth/recovery" class="row-link" data-testid="sec-password-entry">
        <span class="row-label">{{ t('page.m_sec_001.password_manage') }}</span>
        <span class="entry-arrow" aria-hidden="true">›</span>
      </RouterLink>
    </section>
  </main>
</template>

<style scoped>
.sec-root {
  max-width: 560px;
  margin: 0 auto;
  min-height: 100vh;
  background: var(--gray-50);
  color: var(--gray-950);
}
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--space-4);
  background: var(--white);
  border-bottom: var(--border-default);
}
.page-header h1 {
  margin: 0;
  font-size: 20px;
  font-weight: 800;
}
.header-link {
  font-size: 14px;
  color: var(--brand-blue-600);
  text-decoration: none;
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
.row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  min-height: 44px;
  border-bottom: 1px solid var(--gray-100);
}
.row:last-child {
  border-bottom: none;
}
.row-label {
  color: var(--gray-500);
  font-size: 14px;
}
.row-value {
  font-weight: 700;
  font-size: 14px;
}
.row-warn {
  color: var(--warning-600, #b45309);
}
.row-link {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 48px;
  text-decoration: none;
  color: inherit;
}
.entry-arrow {
  color: var(--gray-300);
  font-size: 20px;
}
</style>
