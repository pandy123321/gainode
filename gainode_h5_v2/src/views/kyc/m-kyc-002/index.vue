<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { kycApi } from '../../../api/kyc'
import { t } from '../../../i18n'
import { s3Upload, generateFileName } from '../../../utils/s3Upload'

const router = useRouter()

// 契约缺口：KYC 提交所需 kyc_level / consent_version 无下发端点（S03-P02-KYC-FORM-META）。
// best-effort 用占位常量，后端端点冻结后改由服务端下发。
const KYC_LEVEL = 'standard'
const CONSENT_VERSION = '2026-08-01'

const attachmentRefs = ref<string[]>([])
const uploading = ref(false)
const uploadError = ref('')
const consent = ref(false)
const consentError = ref('')
const submitting = ref(false)
const submitError = ref('')

const fileInput = ref<HTMLInputElement | null>(null)

const canSubmit = computed(() => !uploading.value && !submitting.value)

async function onFiles(e: Event) {
  const input = e.target as HTMLInputElement
  const files = Array.from(input.files ?? [])
  if (!files.length) return
  uploading.value = true
  uploadError.value = ''
  for (const file of files) {
    const ref = await s3Upload(file, generateFileName(file), 'kyc')
    if (ref) attachmentRefs.value.push(ref)
    else uploadError.value = t('page.m_kyc_002.upload_failed')
  }
  uploading.value = false
  input.value = ''
}

function removeRef(index: number) {
  attachmentRefs.value.splice(index, 1)
}

async function onSubmit() {
  submitError.value = ''
  consentError.value = ''
  if (!consent.value) {
    consentError.value = t('page.m_kyc_002.consent_required')
    return
  }
  if (!attachmentRefs.value.length) {
    submitError.value = t('page.m_kyc_002.attachment_required')
    return
  }
  submitting.value = true
  try {
    await kycApi.kycSubmit({
      kyc_level: KYC_LEVEL,
      attachment_refs: attachmentRefs.value,
      consent_version: CONSENT_VERSION,
    })
    router.replace({ name: 'kyc-status' })
  } catch (err) {
    submitError.value = err instanceof Error ? err.message : t('common.error')
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <main class="app-page">
    <header class="app-head">
      <h1>{{ t('page.m_kyc_002.title') }}</h1>
      <p>{{ t('page.m_kyc_002.description') }}</p>
    </header>

    <section class="card">
      <h2 class="card-title">{{ t('page.m_kyc_002.upload_label') }}</h2>
      <p class="hint">{{ t('page.m_kyc_002.upload_hint') }}</p>

      <label class="upload-box" :class="{ 'is-busy': uploading }">
        <input
          ref="fileInput"
          type="file"
          accept="image/jpeg,image/png"
          multiple
          @change="onFiles"
        />
        <span>{{ uploading ? t('common.loading') : t('page.m_kyc_002.upload_label') }}</span>
      </label>

      <ul v-if="attachmentRefs.length" class="ref-list">
        <li v-for="(ref, i) in attachmentRefs" :key="ref">
          <span class="ref-name">{{ ref }}</span>
          <button type="button" class="ref-remove" @click="removeRef(i)">✕</button>
        </li>
      </ul>
      <p v-if="uploadError" class="error" data-testid="upload-error">{{ uploadError }}</p>
    </section>

    <label class="consent-row">
      <input v-model="consent" type="checkbox" class="consent-box" />
      <span>{{ t('page.m_kyc_002.consent_label') }}</span>
    </label>
    <p v-if="consentError" class="error" data-testid="consent-error">{{ consentError }}</p>

    <p v-if="submitError" class="error" data-testid="submit-error">{{ submitError }}</p>

    <button class="cta" :disabled="!canSubmit" @click="onSubmit">
      {{ submitting ? t('page.m_kyc_002.submitting') : t('page.m_kyc_002.primary_action') }}
    </button>
  </main>
</template>

<style scoped>
.app-page {
  max-width: 560px;
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
.card-title { margin: 0 0 var(--space-2); font-size: 15px; color: var(--gray-700); }
.hint { margin: 0 0 var(--space-4); color: var(--gray-400); font-size: 12px; }
.upload-box {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 96px;
  border: 1px dashed var(--gray-300);
  border-radius: var(--radius-md);
  color: var(--brand-blue-600);
  font-weight: 600;
  cursor: pointer;
}
.upload-box.is-busy { opacity: 0.6; }
.upload-box input { display: none; }
.ref-list {
  list-style: none;
  margin: var(--space-3) 0 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: var(--space-2);
}
.ref-list li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-3);
  padding: var(--space-2) var(--space-3);
  background: var(--gray-50);
  border-radius: var(--radius-sm);
}
.ref-name {
  font-size: 13px;
  color: var(--gray-600);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.ref-remove {
  border: none;
  background: none;
  color: var(--danger-600);
  cursor: pointer;
  flex-shrink: 0;
}
.consent-row {
  display: flex;
  align-items: flex-start;
  gap: var(--space-2);
  font-size: 13px;
  color: var(--gray-600);
  line-height: 1.5;
  margin-bottom: var(--space-4);
  cursor: pointer;
}
.consent-box { margin-top: 2px; flex-shrink: 0; }
.error {
  margin: var(--space-3) 0;
  padding: var(--space-3);
  border-radius: var(--radius-md);
  background: var(--danger-100);
  color: var(--danger-600);
  font-size: 13px;
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
.cta:disabled { opacity: 0.55; cursor: not-allowed; }
</style>
