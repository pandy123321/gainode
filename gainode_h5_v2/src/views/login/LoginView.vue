<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { UserApi } from '../../api/services'
import { resetTokenExpiredFlag } from '../../api/legacy'
import { useUserStore } from '../../stores/user'
import { t, getCurrentLanguage } from '../../i18n'
import CountryPicker from '../../components/CountryPicker.vue'

const router = useRouter()
const userStore = useUserStore()

const langLabel = computed(() => getCurrentLanguage().startsWith('zh') ? '中文' : 'EN')

const phone = ref('')
const code = ref('')
const selectedCountry = ref({ flag: '🇨🇳', code: '+86' })
const countdown = ref(0)
const sending = ref(false)
const loggingIn = ref(false)

function startCountdown() {
  countdown.value = 60
  const timer = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) clearInterval(timer)
  }, 1000)
}

async function sendCode() {
  if (!phone.value.trim() || countdown.value > 0) return
  sending.value = true
  const res = await UserApi.sendSmsCode({
    type: 'mobile',
    account: `${selectedCountry.value.code.replace('+', '')}-${phone.value.trim()}`,
    source: 'code',
  })
  sending.value = false
  if (res.code === 0) {
    const c = res.data?.code?.toString() || ''
    if (c) code.value = c
    startCountdown()
  }
}

async function handleLogin() {
  if (loggingIn.value) return
  if (!phone.value.trim() || !code.value.trim()) return
  loggingIn.value = true
  try {
    const res = await UserApi.mobileLogin({
      account: `${selectedCountry.value.code.replace('+', '')}-${phone.value.trim()}`,
      vcode: code.value.trim(),
      type: 'mobile',
      source: 'login',
    })
    if (res.code === 0 && res.data) {
      userStore.saveLogin(res.data.user_id || 0, res.data.token || '')
      resetTokenExpiredFlag()
      await userStore.fetchAfterLogin()
      router.replace('/home')
    }
  } finally {
    loggingIn.value = false
  }
}

</script>

<template>
  <div class="login-screen">
    <div class="login-content">
      <div class="top-row">
        <div></div>
        <router-link to="/lang" class="lang-btn">
          <svg viewBox="0 0 24 24" fill="none" width="20" height="20">
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8" />
            <path d="M3 12h18M12 3c2.5 2.8 4 6 4 9s-1.5 6.2-4 9c-2.5-2.8-4-6-4-9s1.5-6.2 4-9z" stroke="currentColor" stroke-width="1.8" />
          </svg>
          {{ langLabel }}
        </router-link>
      </div>

      <img src="/images/log.png" alt="Logo" class="logo" />
      <h1>{{ t('welcome') }}</h1>
      <p class="slogan">{{ t('slogan') }}</p>

      <label class="field-label">{{ t('phone_number') }}</label>
      <div class="input-box phone-box">
        <CountryPicker v-model="selectedCountry" />
        <input v-model="phone" type="tel" :placeholder="t('enter_phone')" />
      </div>

      <label class="field-label">{{ t('verify_code') }}</label>
      <div class="input-box code-box">
        <input v-model="code" type="text" :placeholder="t('enter_verify_code')" />
        <button class="send-btn" :class="{ loading: sending }" :disabled="countdown > 0 || sending" @click="sendCode">
          <span v-if="sending" class="btn-spinner"></span>
          {{ sending ? '' : countdown > 0 ? `${countdown}s` : t('send_code') }}
        </button>
      </div>

      <button class="login-btn" :class="{ loading: loggingIn }" :disabled="loggingIn" @click="handleLogin">
        <span v-if="loggingIn" class="btn-spinner"></span>
        {{ loggingIn ? '' : t('login') }}
      </button>
    </div>

    <div class="login-footer">
      <span>{{ t('no_account') }}</span>
      <router-link to="/register" class="link">{{ t('register_now') }}</router-link>
    </div>
  </div>
</template>

<style scoped lang="scss">
$bg: #0A0E14;
$elevated: #12181F;
$border: #1E2830;
$green: #3DDC97;
$muted: #8A9CB0;
$dim: #4A5568;

.login-screen {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: $bg;
  padding: 0 24px;
}

.login-content {
  flex: 1;
  padding-top: 16px;

  .top-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
  }

  .lang-btn {
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 6px 12px;
    background: $elevated;
    border: 1px solid $border;
    border-radius: 8px;
    color: $muted;
    font-size: 13px;
    cursor: pointer;
    text-decoration: none;
  }

  .logo {
    display: block;
    margin: 0 auto 32px;
    height: 48px;
  }

  h1 {
    font-size: 25px;
    font-weight: 700;
    color: white;
    text-align: center;
    margin: 0 0 12px;
  }

  .slogan {
    font-size: 13px;
    color: $muted;
    text-align: center;
    margin: 0 0 38px;
  }
}

.field-label {
  display: block;
  font-size: 14px;
  color: $muted;
  font-weight: 500;
  margin-bottom: 10px;
}

.input-box {
  display: flex;
  align-items: center;
  background: $elevated;
  border: 1px solid $border;
  border-radius: 12px;
  margin-bottom: 16px;
  overflow: hidden;

  input {
    flex: 1;
    padding: 16px;
    background: none;
    border: none;
    color: white;
    font-size: 16px;
    outline: none;

    &::placeholder { color: $dim; }
  }
}

.phone-box {
  padding: 0;

  :deep(.country-picker) {
    padding: 16px 0 16px 16px;
    border-right: 1px solid $border;
  }

  input { padding: 16px 12px; }
}

.code-box {
  .send-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 0 16px;
    background: none;
    border: none;
    color: $green;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;

    &:disabled { color: $muted; cursor: default; }

    .btn-spinner {
      width: 14px; height: 14px; border: 2px solid rgba($green, 0.3);
      border-top-color: $green; border-radius: 50%;
      animation: spin 0.6s linear infinite;
    }
  }
}

.login-btn {
  display: flex; align-items: center; justify-content: center; gap: 8px;
  width: 100%;
  height: 56px;
  margin-top: 40px;
  background: linear-gradient(90deg, #26FFBF, #00D98C);
  border: none;
  border-radius: 16px;
  color: #0A0E14;
  font-size: 18px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 8px 24px rgba($green, 0.35);
  transition: opacity 0.2s;

  &:disabled { opacity: 0.6; cursor: not-allowed; }

  .btn-spinner {
    width: 20px; height: 20px; border: 2px solid rgba(#0A0E14, 0.3);
    border-top-color: #0A0E14; border-radius: 50%;
    animation: spin 0.6s linear infinite;
  }
}

.login-footer {
  padding: 24px 0;
  text-align: center;
  font-size: 14px;
  color: $muted;

  .link {
    color: $green;
    font-weight: 600;
    text-decoration: none;
  }
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
