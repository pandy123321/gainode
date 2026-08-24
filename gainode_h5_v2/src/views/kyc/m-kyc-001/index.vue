<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useKycStore } from '../../../stores/kyc'
import { useEntitlementStore } from '../../../stores/entitlement'
import { t } from '../../../i18n'
import FiveStateContainer from '../../../components/FiveStateContainer.vue'
import { kycStatusLabel } from '../kycStatus'
import type { FeatureEntitlement } from '../../../api/kyc'
import DataStateBadge from '../../../components/DataStateBadge.vue'

const router = useRouter()
const kyc = useKycStore()
const ent = useEntitlementStore()

const state = computed(() => {
  if (kyc.loading || ent.loading) return 'loading'
  if (kyc.error || ent.error) return 'error'
  return 'default'
})

const errorMessage = computed(() => kyc.error || ent.error || '')

const statusLabel = computed(() => kycStatusLabel(kyc.status))

const features = computed(
  (): { key: string; label: string; item: FeatureEntitlement | null }[] => [
    { key: 'global_p', label: t('kyc.feature.global_p'), item: ent.globalP },
    { key: 'ai', label: t('kyc.feature.ai'), item: ent.ai },
    { key: 'prediction', label: t('kyc.feature.prediction'), item: ent.prediction },
  ],
)

function primaryLabel(): string {
  const s = kyc.status
  if (s === 'not_started') return t('page.m_kyc_001.primary_action')
  if (s === 'needs_info') return t('page.m_kyc_001.action_supplement')
  return t('page.m_kyc_001.action_view')
}

function onPrimary() {
  const s = kyc.status
  if (s === 'not_started' || s === 'needs_info') router.push({ name: 'kyc-form' })
  else router.push({ name: 'kyc-status' })
}

function reload() {
  kyc.fetch()
  ent.fetch()
}

onMounted(reload)
</script>

<template>
  <main class="app-page">
    <header class="app-head">
      <h1>{{ t('page.m_kyc_001.title') }}</h1>
      <DataStateBadge page-id="M-KYC-001" />
      <p>{{ t('page.m_kyc_001.description') }}</p>
    </header>

    <FiveStateContainer :state="state" :error-message="errorMessage" @retry="reload">
      <section class="card status-card">
        <span class="status-dot" :data-status="kyc.status || 'not_started'" aria-hidden="true" />
        <div>
          <div class="status-label">{{ statusLabel }}</div>
          <div class="status-sub">{{ t('kyc.status.' + (kyc.status || 'not_started')) }}</div>
        </div>
      </section>

      <section class="card">
        <h2 class="card-title">{{ t('page.m_kyc_001.capabilities_title') }}</h2>
        <div v-for="f in features" :key="f.key" class="cap-row">
          <span class="cap-name">{{ f.label }}</span>
          <span class="cap-state" :class="{ 'cap-denied': !f.item?.allowed }">
            {{ f.item?.allowed ? t('kyc.feature.allowed') : t('kyc.feature.restricted') }}
          </span>
        </div>
        <p v-if="!features.some((f) => f.item?.allowed)" class="cap-empty">
          {{ t('common.empty') }}
        </p>
      </section>

      <button class="cta" @click="onPrimary">{{ primaryLabel() }}</button>
    </FiveStateContainer>
  </main>
</template>

<style scoped>
.app-page {
  max-width: 640px;
  margin: 0 auto;
  padding: var(--space-6) var(--space-4) var(--space-10);
  color: var(--gray-950);
}
.app-head h1 {
  margin: 0 0 var(--space-2);
  font-size: 24px;
}
.app-head p {
  margin: 0 0 var(--space-6);
  color: var(--gray-500);
  font-size: 14px;
}
.card {
  background: var(--white);
  border: var(--border-default);
  border-radius: var(--radius-lg);
  padding: var(--space-5);
  margin-bottom: var(--space-4);
}
.card-title {
  margin: 0 0 var(--space-3);
  font-size: 15px;
  color: var(--gray-700);
}
.status-card {
  display: flex;
  align-items: center;
  gap: var(--space-4);
  min-height: 112px;
}
.status-dot {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  flex-shrink: 0;
  background: var(--gray-300);
}
.status-dot[data-status='approved'] { background: var(--success-600); }
.status-dot[data-status='pending'],
.status-dot[data-status='review'] { background: var(--info-600); }
.status-dot[data-status='needs_info'] { background: var(--warning-600); }
.status-dot[data-status='rejected'] { background: var(--danger-600); }
.status-label {
  font-size: 18px;
  font-weight: 700;
}
.status-sub {
  color: var(--gray-500);
  font-size: 13px;
}
.cap-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 56px;
  border-bottom: 1px solid var(--gray-100);
}
.cap-row:last-child { border-bottom: none; }
.cap-name { font-size: 15px; }
.cap-state {
  font-size: 13px;
  font-weight: 600;
  color: var(--success-600);
}
.cap-denied { color: var(--gray-400); }
.cap-empty {
  margin: var(--space-3) 0 0;
  color: var(--gray-400);
  font-size: 13px;
  text-align: center;
}
.cta {
  width: 100%;
  height: 48px;
  border: none;
  border-radius: var(--radius-lg);
  background: var(--brand-blue-600);
  color: var(--white);
  font-size: 16px;
  font-weight: 700;
  cursor: pointer;
}
</style>
