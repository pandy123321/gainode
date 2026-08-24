<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { authApi, type AuthTokenResponse } from '../../../api/auth'
import { useSessionStore } from '../../../stores/session'
import { useAuthFlowStore } from '../../../stores/auth'
import { t } from '../../../i18n'
import { maskAccount } from '../../../utils/mask'
import AuthShell from '../AuthShell.vue'
import { authErrorMessage } from '../authError'
import DataStateBadge from '../../../components/DataStateBadge.vue'

const router = useRouter()
const session = useSessionStore()
const authFlow = useAuthFlowStore()

const account = ref('')
const password = ref('')
const submitting = ref(false)
const errorMessage = ref('')

// AuthTokenResponse 未冻结 refresh_token（缺口 S03-P02-AUTH-REFRESH-TOKEN），
// 防御性读取，缺省则 session 无法自动续期，仅影响 token 过期后重新登录。
function extractRefreshToken(data: AuthTokenResponse): string | null {
  return (data as AuthTokenResponse & { refresh_token?: string }).refresh_token ?? null
}

function validate(): string | null {
  if (!account.value.trim()) return t('auth.invalid_account')
  if (!password.value) return t('auth.invalid_password')
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
    const resp = await authApi.login({
      account: account.value.trim(),
      password: password.value,
    })
    const data = resp.data
    session.setTokens(data.access_token, extractRefreshToken(data))
    if (data.mfa_required) {
      // 高风险登录 → MFA 二次验证（M-AUTH-005），保留 session_id 续验上下文
      authFlow.setContext({
        account: account.value.trim(),
        purpose: 'login',
        sessionId: data.session_id,
        maskedAccount: maskAccount(account.value.trim()),
      })
      router.replace('/auth/mfa')
    } else {
      router.replace('/')
    }
  } catch (e) {
    errorMessage.value = authErrorMessage(e)
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <AuthShell :title="t('page.m_auth_001.title')" :description="t('page.m_auth_001.description')">
  <DataStateBadge page-id="M-AUTH-001" />
    <div v-if="errorMessage" class="auth-error" data-testid="auth-error">
      {{ errorMessage }}
    </div>

    <label class="auth-field">
      <span class="auth-label">{{ t('auth.account_email') }}</span>
      <input
        v-model="account"
        class="auth-input"
        type="text"
        autocomplete="username"
        :placeholder="t('auth.account_placeholder_email')"
        data-testid="login-account"
      />
    </label>

    <label class="auth-field">
      <span class="auth-label">{{ t('auth.password') }}</span>
      <input
        v-model="password"
        class="auth-input"
        type="password"
        autocomplete="current-password"
        :placeholder="t('auth.password_placeholder')"
        data-testid="login-password"
        @keyup.enter="submit"
      />
    </label>

    <button
      class="auth-btn"
      :disabled="submitting"
      data-testid="login-submit"
      @click="submit"
    >
      <span v-if="submitting" class="auth-spinner" aria-hidden="true" />
      {{ submitting ? t('common.loading') : t('page.m_auth_001.primary_action') }}
    </button>

    <div class="auth-row" style="justify-content: space-between">
      <router-link to="/auth/recovery" class="auth-link">
        {{ t('auth.forgot_password') }}
      </router-link>
      <span>{{ t('auth.no_account') }} <router-link to="/auth/register" class="auth-link">{{ t('auth.register_now') }}</router-link></span>
    </div>

    <template #footer>
      <router-link to="/auth/register" class="auth-link">{{ t('auth.register_now') }}</router-link>
    </template>
  </AuthShell>
</template>
