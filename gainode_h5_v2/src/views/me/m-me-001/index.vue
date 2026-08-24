<script setup lang="ts">
import { onMounted } from 'vue'
import { useProfileStore } from '../../../stores/profile'
import { useKycStore } from '../../../stores/kyc'
import { useSecurityStore } from '../../../stores/security'
import { t } from '../../../i18n'
import FiveStateContainer from '../../../components/FiveStateContainer.vue'
import DataStateBadge from '../../../components/DataStateBadge.vue'

const profile = useProfileStore()
const kyc = useKycStore()
const security = useSecurityStore()

const entries = [
  { key: 'asset', to: '/asset', label: t('page.m_me_001.entry_asset') },
  { key: 'power', to: '/power', label: t('page.m_me_001.entry_power') },
  { key: 'otc', to: '/otc', label: t('page.m_me_001.entry_otc') },
  { key: 'kyc', to: '/kyc', label: t('page.m_me_001.entry_kyc') },
  { key: 'notice', to: '/notices', label: t('page.m_me_001.entry_notice') },
  { key: 'security', to: '/security', label: t('page.m_me_001.entry_security') },
  { key: 'settings', to: '/settings', label: t('page.m_me_001.entry_settings') },
  { key: 'support', to: '/support', label: t('page.m_me_001.entry_support') },
]

onMounted(() => {
  if (!profile.loaded) profile.fetch()
  if (!kyc.loaded) kyc.fetch()
  if (!security.profile) security.fetchProfile()
})
</script>

<template>
  <main class="me-root">
    <header class="page-header">
      <h1 data-testid="me-title">{{ t('page.m_me_001.title') }}</h1>
      <DataStateBadge page-id="M-ME-001" />
    </header>

    <!-- 用户摘要（脱敏，不把资产/数字做成第一视觉焦点） -->
    <section class="card" data-testid="me-profile">
      <FiveStateContainer
        :state="profile.loading ? 'loading' : profile.error ? 'error' : 'default'"
        :error-message="profile.error || ''"
        @retry="profile.fetch"
      >
        <template v-if="profile.user">
          <div class="profile-name">{{ profile.displayName || t('page.m_me_001.unknown_user') }}</div>
          <div class="profile-meta">
            <span class="chip">{{ t('user.status.' + profile.status) }}</span>
            <span class="chip" data-testid="me-global-p">
              {{ t('user.global_p_level') }} {{ profile.globalPLevel }}
            </span>
          </div>
        </template>
      </FiveStateContainer>
    </section>

    <!-- KYC / 安全状态摘要 -->
    <section class="card" data-testid="me-status">
      <div class="row">
        <span class="row-label">{{ t('page.m_me_001.kyc_status') }}</span>
        <span class="row-value" data-testid="me-kyc-status">
          {{ kyc.status ? t('kyc.status.' + kyc.status) : t('common.unknown') }}
        </span>
      </div>
      <div class="row">
        <span class="row-label">{{ t('page.m_me_001.mfa_status') }}</span>
        <span class="row-value" data-testid="me-mfa-status">
          {{
            security.mfaEnrolled.length
              ? t('page.m_me_001.mfa_enrolled')
              : t('page.m_me_001.mfa_not_enrolled')
          }}
        </span>
      </div>
    </section>

    <!-- 功能入口分组（Me 是入口页，不做复杂业务操作） -->
    <section class="card" data-testid="me-entries">
      <RouterLink
        v-for="e in entries"
        :key="e.key"
        :to="e.to"
        class="entry-row"
        :data-testid="'me-entry-' + e.key"
      >
        <span class="entry-label">{{ e.label }}</span>
        <span class="entry-arrow" aria-hidden="true">›</span>
      </RouterLink>
    </section>
  </main>
</template>

<style scoped>
.me-root {
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
.profile-name {
  font-size: 18px;
  font-weight: 800;
}
.profile-meta {
  display: flex;
  gap: var(--space-2);
  margin-top: var(--space-3);
}
.chip {
  padding: 2px 8px;
  border-radius: var(--radius-md);
  background: var(--gray-100);
  color: var(--gray-600);
  font-size: 12px;
  font-weight: 600;
}
.row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  min-height: 48px;
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
.entry-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 56px;
  border-bottom: 1px solid var(--gray-100);
  text-decoration: none;
  color: var(--gray-800);
}
.entry-row:last-child {
  border-bottom: none;
}
.entry-label {
  font-size: 15px;
  font-weight: 600;
}
.entry-arrow {
  color: var(--gray-300);
  font-size: 20px;
}
.entry-hint {
  color: var(--gray-400);
  font-size: 12px;
}
.entry-disabled {
  cursor: not-allowed;
  opacity: 0.7;
}
</style>
