<script setup lang="ts">
import { ref, computed, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { authApi } from '../../../api/auth'
import { t } from '../../../i18n'
import { maskAccount } from '../../../utils/mask'
import AuthShell from '../AuthShell.vue'
import { authErrorMessage } from '../authError'
import DataStateBadge from '../../../components/DataStateBadge.vue'

const RESEND_SECONDS = 60

type Step = 'account' | 'otp' | 'reset' | 'done'

const router = useRouter()

const step = ref<Step>('account')
const account = ref('')
const vcode = ref('')
const password = ref('')
const confirmPassword = ref('')
const countdown = ref(0)
const busy = ref(false)
const errorMessage = ref('')
let timer: ReturnType<typeof setInterval> | null = null

const title = computed(() => t('page.m_auth_004.title'))

function startCountdown() {
  countdown.value = RESEND_SECONDS
  stopTimer()
  timer = setInterval(() => {
    countdown.value -= 1
    if (countdown.value <= 0) stopTimer()
  }, 1000)
}
function stopTimer() {
  if (timer) {
    clearInterval(timer)
    timer = null
  }
}

async function submitAccount() {
  if (busy.value) return
  if (!account.value.trim()) {
    errorMessage.value = t('auth.invalid_account')
    return
  }
  errorMessage.value = ''
  busy.value = true
  try {
    // 发起找回；后端不泄露账号是否存在（防枚举）
    await authApi.recovery({ account: account.value.trim() })
    step.value = 'otp'
    startCountdown()
  } catch (e) {
    errorMessage.value = authErrorMessage(e)
  } finally {
    busy.value = false
  }
}

async function resend() {
  if (busy.value || countdown.value > 0) return
  busy.value = true
  errorMessage.value = ''
  try {
    await authApi.otpResend({ account: account.value.trim(), type: 'email', source: 'forget' })
    startCountdown()
  } catch (e) {
    errorMessage.value = authErrorMessage(e)
  } finally {
    busy.value = false
  }
}

async function verifyOtp() {
  if (busy.value) return
  if (!vcode.value.trim()) {
    errorMessage.value = t('auth.invalid_vcode')
    return
  }
  errorMessage.value = ''
  busy.value = true
  try {
    // OpenAPI 无 /auth/recovery/verify，用 otp_verify(source=forget)（缺口 S03-P02-AUTH-RECOVERY-VERIFY）
    await authApi.otpVerify({
      account: account.value.trim(),
      vcode: vcode.value.trim(),
      type: 'email',
      source: 'forget',
    })
    step.value = 'reset'
  } catch (e) {
    errorMessage.value = authErrorMessage(e)
  } finally {
    busy.value = false
  }
}

async function resetPassword() {
  if (busy.value) return
  if (!password.value) {
    errorMessage.value = t('auth.invalid_password')
    return
  }
  if (password.value.length < 8) {
    errorMessage.value = t('auth.password_too_short')
    return
  }
  if (password.value !== confirmPassword.value) {
    errorMessage.value = t('auth.password_mismatch')
    return
  }
  errorMessage.value = ''
  busy.value = true
  try {
    await authApi.passwordReset({
      account: account.value.trim(),
      vcode: vcode.value.trim(),
      password: password.value,
    })
    step.value = 'done'
    stopTimer()
  } catch (e) {
    errorMessage.value = authErrorMessage(e)
  } finally {
    busy.value = false
  }
}

onUnmounted(stopTimer)
</script>

<template>
  <AuthShell :title="title" :description="t('page.m_auth_004.description')">
  <DataStateBadge page-id="M-AUTH-004" />
    <div v-if="errorMessage" class="auth-error" data-testid="auth-error">
      {{ errorMessage }}
    </div>

    <!-- Step 1: account -->
    <template v-if="step === 'account'">
      <label class="auth-field">
        <span class="auth-label">{{ t('auth.account_email') }}</span>
        <input
          v-model="account"
          class="auth-input"
          type="email"
          autocomplete="username"
          :placeholder="t('auth.account_placeholder_email')"
          data-testid="recovery-account"
          @keyup.enter="submitAccount"
        />
      </label>
      <button class="auth-btn" :disabled="busy" data-testid="recovery-submit" @click="submitAccount">
        <span v-if="busy" class="auth-spinner" aria-hidden="true" />
        {{ busy ? t('common.loading') : t('page.m_auth_004.primary_action') }}
      </button>
    </template>

    <!-- Step 2: OTP -->
    <template v-else-if="step === 'otp'">
      <p class="auth-desc">{{ t('page.m_auth_003.description', { account: maskAccount(account) }) }}</p>
      <label class="auth-field">
        <span class="auth-label">{{ t('auth.vcode') }}</span>
        <div class="auth-code-row">
          <input
            v-model="vcode"
            class="auth-input"
            type="text"
            inputmode="numeric"
            maxlength="6"
            autocomplete="one-time-code"
            :placeholder="t('auth.vcode_placeholder')"
            data-testid="recovery-vcode"
            @keyup.enter="verifyOtp"
          />
          <button type="button" class="auth-code-btn" :disabled="countdown > 0 || busy" data-testid="recovery-resend" @click="resend">
            <span v-if="busy" class="auth-spinner" aria-hidden="true" />
            {{ busy ? '' : countdown > 0 ? t('auth.countdown', { seconds: String(countdown) }) : t('auth.resend') }}
          </button>
        </div>
      </label>
      <button class="auth-btn" :disabled="busy" data-testid="recovery-otp-submit" @click="verifyOtp">
        <span v-if="busy" class="auth-spinner" aria-hidden="true" />
        {{ busy ? t('common.loading') : t('page.m_auth_003.primary_action') }}
      </button>
    </template>

    <!-- Step 3: reset password -->
    <template v-else-if="step === 'reset'">
      <label class="auth-field">
        <span class="auth-label">{{ t('auth.password') }}</span>
        <input
          v-model="password"
          class="auth-input"
          type="password"
          autocomplete="new-password"
          :placeholder="t('auth.password_placeholder')"
          data-testid="reset-password"
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
          data-testid="reset-confirm"
          @keyup.enter="resetPassword"
        />
      </label>
      <button class="auth-btn" :disabled="busy" data-testid="reset-submit" @click="resetPassword">
        <span v-if="busy" class="auth-spinner" aria-hidden="true" />
        {{ busy ? t('common.loading') : t('page.m_auth_004.primary_action') }}
      </button>
    </template>

    <!-- Step 4: done -->
    <template v-else>
      <p class="auth-desc" data-testid="reset-done">{{ t('auth.password_reset_done') }}</p>
      <button class="auth-btn" data-testid="reset-to-login" @click="router.replace('/auth/login')">
        {{ t('auth.back_to_login') }}
      </button>
    </template>

    <div class="auth-row" style="justify-content: center">
      <router-link to="/auth/login" class="auth-link">{{ t('auth.back_to_login') }}</router-link>
    </div>
  </AuthShell>
</template>
