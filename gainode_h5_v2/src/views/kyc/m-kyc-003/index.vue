<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useKycStore } from '../../../stores/kyc'
import { t } from '../../../i18n'
import FiveStateContainer from '../../../components/FiveStateContainer.vue'
import { kycStatusLabel } from '../kycStatus'

const router = useRouter()
const kyc = useKycStore()

const state = computed(() => {
  if (kyc.loading) return 'loading'
  if (kyc.error) return 'error'
  return 'default'
})

const statusLabel = computed(() => kycStatusLabel(kyc.status))

const needsSupplement = computed(() => kyc.status === 'needs_info')

function fmtTime(ts?: number | null): string {
  if (!ts) return ''
  return new Date(ts * 1000).toLocaleString()
}

const timeline = computed(() => {
  const rows: { label: string; time: string; active: boolean }[] = []
  const k = kyc.kase
  if (!k) return rows
  rows.push({ label: t('kyc.status.pending'), time: fmtTime(k.submitted_at), active: true })
  if (k.reviewed_at) {
    rows.push({ label: statusLabel.value, time: fmtTime(k.reviewed_at), active: true })
  }
  return rows
})

function onPrimary() {
  if (needsSupplement.value) router.push({ name: 'kyc-form' })
  else router.push({ name: 'root' })
}

onMounted(() => kyc.fetch())
</script>

<template>
  <main class="app-page">
    <header class="app-head">
      <h1>{{ t('page.m_kyc_003.title') }}</h1>
      <p>{{ t('page.m_kyc_003.description') }}</p>
    </header>

    <FiveStateContainer :state="state" :error-message="kyc.error || ''" @retry="kyc.fetch">
      <section class="card status-card">
        <span class="status-dot" :data-status="kyc.status || 'not_started'" aria-hidden="true" />
        <div>
          <div class="status-label">{{ statusLabel }}</div>
          <div v-if="kyc.kase?.reason_text_key" class="status-reason">
            {{ t(kyc.kase.reason_text_key) }}
          </div>
        </div>
      </section>

      <section class="card">
        <h2 class="card-title">{{ t('page.m_kyc_003.timeline_title') }}</h2>
        <div v-for="(row, i) in timeline" :key="i" class="tl-row">
          <span class="tl-dot" aria-hidden="true" />
          <span class="tl-label">{{ row.label }}</span>
          <span class="tl-time">{{ row.time }}</span>
        </div>
      </section>

      <button class="cta" @click="onPrimary">
        {{ needsSupplement ? t('page.m_kyc_003.action_supplement') : t('page.m_kyc_003.primary_action') }}
      </button>
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
.app-head h1 { margin: 0 0 var(--space-2); font-size: 24px; }
.app-head p { margin: 0 0 var(--space-6); color: var(--gray-500); font-size: 14px; }
.card {
  background: var(--white);
  border: var(--border-default);
  border-radius: var(--radius-lg);
  padding: var(--space-5);
  margin-bottom: var(--space-4);
}
.card-title { margin: 0 0 var(--space-3); font-size: 15px; color: var(--gray-700); }
.status-card {
  display: flex;
  align-items: center;
  gap: var(--space-4);
  min-height: 112px;
}
.status-dot {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  flex-shrink: 0;
  background: var(--gray-300);
}
.status-dot[data-status='approved'] { background: var(--success-600); }
.status-dot[data-status='pending'],
.status-dot[data-status='review'] { background: var(--info-600); }
.status-dot[data-status='needs_info'] { background: var(--warning-600); }
.status-dot[data-status='rejected'] { background: var(--danger-600); }
.status-label { font-size: 18px; font-weight: 700; }
.status-reason { color: var(--gray-500); font-size: 13px; margin-top: var(--space-1); }
.tl-row {
  display: flex;
  align-items: center;
  gap: var(--space-3);
  min-height: 56px;
}
.tl-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: var(--brand-blue-600);
  flex-shrink: 0;
}
.tl-label { font-size: 14px; color: var(--gray-700); }
.tl-time { margin-left: auto; font-size: 12px; color: var(--gray-400); }
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
