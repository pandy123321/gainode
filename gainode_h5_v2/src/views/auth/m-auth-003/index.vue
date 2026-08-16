<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { authApi } from '../../../api/auth'
import { useAuthFlowStore } from '../../../stores/auth'
import { t } from '../../../i18n'
import AuthShell from '../AuthShell.vue'
import { authErrorMessage } from '../authError'

const RESEND_SECONDS = 60

const router = useRouter()
const authFlow = useAuthFlowStore()

const vcode = ref('')
const countdown = ref(0)
const sending = ref(false)
const verifying = ref(false)
const errorMessage = ref('')
let timer: ReturnType<typeof setInterval> | null = null

const description = computed(() =>
  t('page.m_auth_003.description', { account: authFlow.maskedAccount || authFlow.account }),
)

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

async function resend() {
  if (sending.value || countdown.value > 0) return
  sending.value = true
  errorMessage.value = ''
  try {
    await authApi.otpResend({
      account: authFlow.account,
      type: authFlow.accountType,
      source: authFlow.source,
    })
    startCountdown()
  } catch (e) {
    errorMessage.value = authErrorMessage(e)
  } finally {
    sending.value = false
  }
}

async function verify() {
  if (verifying.value) return
  if (!vcode.value.trim()) {
    errorMessage.value = t('auth.invalid_vcode')
    return
  }
  errorMessage.value = ''
  verifying.value = true
  try {
    await authApi.otpVerify({
      account: authFlow.account,
      vcode: vcode.value.trim(),
      type: authFlow.accountType,
      source: authFlow.source,
    })
    // 成功按 purpose 去下一步；register 验证后需登录获取 session
    if (authFlow.purpose === 'register') {
      authFlow.reset()
      router.replace('/auth/login')
    } else {
      router.replace('/')
    }
  } catch (e) {
    errorMessage.value = authErrorMessage(e)
  } finally {
    verifying.value = false
  }
}

onMounted(() => {
  // 无上下文（深链直入）安全降级到登录
  if (!authFlow.account) {
    router.replace('/auth/login')
    return
  }
  startCountdown()
})
onUnmounted(stopTimer)
</script>

<template>
  <AuthShell :title="t('page.m_auth_003.title')" :description="description">
    <div v-if="errorMessage" class="auth-error" data-testid="auth-error">
      {{ errorMessage }}
    </div>

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
          data-testid="otp-vcode"
          @keyup.enter="verify"
        />
        <button
          type="button"
          class="auth-code-btn"
          :disabled="countdown > 0 || sending"
          data-testid="otp-resend"
          @click="resend"
        >
          <span v-if="sending" class="auth-spinner" aria-hidden="true" />
          {{ sending ? '' : countdown > 0 ? t('auth.countdown', { seconds: String(countdown) }) : t('auth.resend') }}
        </button>
      </div>
    </label>

    <button class="auth-btn" :disabled="verifying" data-testid="otp-submit" @click="verify">
      <span v-if="verifying" class="auth-spinner" aria-hidden="true" />
      {{ verifying ? t('common.loading') : t('page.m_auth_003.primary_action') }}
    </button>

    <div class="auth-row" style="justify-content: center">
      <router-link to="/auth/login" class="auth-link">{{ t('auth.back_to_login') }}</router-link>
    </div>
  </AuthShell>
</template>
