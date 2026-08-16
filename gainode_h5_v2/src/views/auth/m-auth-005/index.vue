<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { authApi, type AuthTokenResponse } from '../../../api/auth'
import { useSessionStore } from '../../../stores/session'
import { useAuthFlowStore } from '../../../stores/auth'
import { t } from '../../../i18n'
import AuthShell from '../AuthShell.vue'
import { authErrorMessage } from '../authError'

const router = useRouter()
const session = useSessionStore()
const authFlow = useAuthFlowStore()

const code = ref('')
const verifying = ref(false)
const errorMessage = ref('')

// AuthTokenResponse 未冻结 refresh_token（同 S03-P02-AUTH-REFRESH-TOKEN）
function extractRefreshToken(data: AuthTokenResponse): string | null {
  return (data as AuthTokenResponse & { refresh_token?: string }).refresh_token ?? null
}

async function verify() {
  if (verifying.value) return
  if (!code.value.trim()) {
    errorMessage.value = t('auth.invalid_vcode')
    return
  }
  errorMessage.value = ''
  verifying.value = true
  try {
    // allowed_methods/expires_at 未冻结（缺口 S03-P02-AUTH-MFA-METHODS），仅用 totp code + session_id
    const resp = await authApi.mfaVerify({
      code: code.value.trim(),
      session_id: authFlow.sessionId,
    })
    session.setTokens(resp.data.access_token, extractRefreshToken(resp.data))
    authFlow.reset()
    router.replace('/')
  } catch (e) {
    errorMessage.value = authErrorMessage(e)
  } finally {
    verifying.value = false
  }
}

onMounted(() => {
  // MFA challenge 绑定原登录 session；深链直入无上下文 → 安全降级登录
  if (!authFlow.sessionId) {
    router.replace('/auth/login')
  }
})
</script>

<template>
  <AuthShell :title="t('page.m_auth_005.title')" :description="t('page.m_auth_005.description')">
    <div v-if="errorMessage" class="auth-error" data-testid="auth-error">
      {{ errorMessage }}
    </div>

    <p class="auth-desc">{{ t('auth.mfa_hint') }}</p>

    <label class="auth-field">
      <span class="auth-label">{{ t('auth.vcode') }}</span>
      <input
        v-model="code"
        class="auth-input"
        type="text"
        inputmode="numeric"
        maxlength="6"
        autocomplete="one-time-code"
        :placeholder="t('auth.vcode_placeholder')"
        data-testid="mfa-code"
        @keyup.enter="verify"
      />
    </label>

    <button class="auth-btn" :disabled="verifying" data-testid="mfa-submit" @click="verify">
      <span v-if="verifying" class="auth-spinner" aria-hidden="true" />
      {{ verifying ? t('common.loading') : t('page.m_auth_005.primary_action') }}
    </button>
  </AuthShell>
</template>
