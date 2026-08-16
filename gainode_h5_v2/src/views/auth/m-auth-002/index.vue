<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { authApi, type AccountType } from '../../../api/auth'
import { useAuthFlowStore } from '../../../stores/auth'
import { t } from '../../../i18n'
import { maskAccount } from '../../../utils/mask'
import AuthShell from '../AuthShell.vue'
import { authErrorMessage } from '../authError'

// 当前条款/隐私 consent 版本。来源端点未冻结（缺口 S03-P02-AUTH-CONSENT-VERSION），
// 对齐后端当前生效版本后更新；不随客户端随意变更。
const CONSENT_VERSION = '2026-08-01'

const router = useRouter()
const authFlow = useAuthFlowStore()

const accountType = ref<AccountType>('email')
const account = ref('')
const password = ref('')
const confirmPassword = ref('')
const inviteCode = ref('')
const agreed = ref(false)
const submitting = ref(false)
const errorMessage = ref('')

function validate(): string | null {
  if (!account.value.trim()) return t('auth.invalid_account')
  if (!password.value) return t('auth.invalid_password')
  if (password.value.length < 8) return t('auth.password_too_short')
  if (password.value !== confirmPassword.value) return t('auth.password_mismatch')
  if (!agreed.value) return t('auth.consent_required')
  return null
}

async function submit() {
  if (submitting.value) return
  const msg = validate()
  if (msg) {
    errorMessage.value = msg
    return
  }
  errorMessage.value = ''
  submitting.value = true
  try {
    await authApi.register({
      account: account.value.trim(),
      account_type: accountType.value,
      consent_version: CONSENT_VERSION,
      password: password.value,
      invite_code: inviteCode.value.trim() || null,
      locale: undefined,
      timezone: undefined,
    })
    // 注册成功 → OTP 验证（M-AUTH-003），source=register
    authFlow.setContext({
      account: account.value.trim(),
      accountType: accountType.value,
      source: 'register',
      purpose: 'register',
      maskedAccount: maskAccount(account.value.trim()),
    })
    router.replace('/auth/otp')
  } catch (e) {
    errorMessage.value = authErrorMessage(e)
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <AuthShell :title="t('page.m_auth_002.title')" :description="t('page.m_auth_002.description')">
    <div v-if="errorMessage" class="auth-error" data-testid="auth-error">
      {{ errorMessage }}
    </div>

    <div class="auth-row" role="radiogroup" aria-label="account type">
      <button
        type="button"
        class="auth-btn auth-btn-secondary"
        :class="{ 'auth-btn-active': accountType === 'email' }"
        :aria-pressed="accountType === 'email'"
        @click="accountType = 'email'"
      >
        {{ t('auth.account_email') }}
      </button>
      <button
        type="button"
        class="auth-btn auth-btn-secondary"
        :class="{ 'auth-btn-active': accountType === 'mobile' }"
        :aria-pressed="accountType === 'mobile'"
        @click="accountType = 'mobile'"
      >
        {{ t('auth.account_mobile') }}
      </button>
    </div>

    <label class="auth-field">
      <span class="auth-label">{{ accountType === 'email' ? t('auth.account_email') : t('auth.account_mobile') }}</span>
      <input
        v-model="account"
        class="auth-input"
        :type="accountType === 'email' ? 'email' : 'tel'"
        autocomplete="username"
        :placeholder="accountType === 'email' ? t('auth.account_placeholder_email') : t('auth.account_placeholder_mobile')"
        data-testid="register-account"
      />
    </label>

    <label class="auth-field">
      <span class="auth-label">{{ t('auth.password') }}</span>
      <input
        v-model="password"
        class="auth-input"
        type="password"
        autocomplete="new-password"
        :placeholder="t('auth.password_placeholder')"
        data-testid="register-password"
      />
    </label>

    <label class="auth-field">
      <span class="auth-label">{{ t('auth.confirm_password') }}</span>
      <input
        v-model="confirmPassword"
        class="auth-input"
        type="password"
        autocomplete="new-password"
        :placeholder="t('auth.confirm_password_placeholder')"
        data-testid="register-confirm"
      />
    </label>

    <label class="auth-field">
      <span class="auth-label">{{ t('auth.invite_code') }}</span>
      <input
        v-model="inviteCode"
        class="auth-input"
        type="text"
        :placeholder="t('auth.invite_code_placeholder')"
        data-testid="register-invite"
      />
    </label>

    <label class="auth-checkbox-row" data-testid="register-consent">
      <span class="auth-checkbox" :class="{ 'auth-checkbox--checked': agreed }" @click="agreed = !agreed">
        <svg v-if="agreed" viewBox="0 0 16 16" width="12" height="12" aria-hidden="true">
          <path d="M3 8l3 3 7-7" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none" />
        </svg>
      </span>
      <span>{{ t('auth.consent_label') }}</span>
    </label>

    <button class="auth-btn" :disabled="submitting" data-testid="register-submit" @click="submit">
      <span v-if="submitting" class="auth-spinner" aria-hidden="true" />
      {{ submitting ? t('common.loading') : t('page.m_auth_002.primary_action') }}
    </button>

    <div class="auth-row" style="justify-content: center">
      <span>{{ t('auth.have_account') }} <router-link to="/auth/login" class="auth-link">{{ t('auth.login_now') }}</router-link></span>
    </div>
  </AuthShell>
</template>

<style scoped>
.auth-btn-active {
  border-color: var(--brand-blue-600);
  background: var(--info-100);
  color: var(--brand-blue-600);
}
</style>
