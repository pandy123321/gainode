<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { UserApi } from '../../api/services'
import { resetTokenExpiredFlag } from '../../api/legacy'
import { useUserStore } from '../../stores/user'
import { t } from '../../i18n'
import CountryPicker from '../../components/CountryPicker.vue'

const router = useRouter()
const userStore = useUserStore()

const inviteCode = ref(getInitialInviteCode())
const phone = ref('')
const code = ref('')
const agreed = ref(true)
const selectedCountry = ref({ flag: '🇨🇳', code: '+86' })
const countdown = ref(0)
const sending = ref(false)
const registering = ref(false)

function getInitialInviteCode(): string {
  try {
    const params = new URLSearchParams(window.location.search)
    const c = params.get('inviteCode')
    if (c) { sessionStorage.setItem('inviteCode', c); return c }
    return sessionStorage.getItem('inviteCode') || ''
  } catch { return '' }
}

function startCountdown() {
  countdown.value = 60
  const timer = setInterval(() => {
    countdown.value--
    if (countdown.value <= 0) clearInterval(timer)
  }, 1000)
}

async function sendCode() {
  if (!phone.value.trim() || countdown.value > 0 || sending.value) return
  sending.value = true
  try {
    const res = await UserApi.sendSmsCode({
      type: 'mobile',
      account: `${selectedCountry.value.code.replace('+', '')}-${phone.value.trim()}`,
      source: 'code',
    })
    if (res.code === 0) {
      const c = res.data?.code?.toString() || ''
      if (c) code.value = c
      startCountdown()
    }
  } finally {
    sending.value = false
  }
}

async function handleRegister() {
  if (registering.value) return
  if (!phone.value.trim() || !code.value.trim() || !inviteCode.value.trim()) return
  registering.value = true
  try {
    const res = await UserApi.mobileLogin({
      account: `${selectedCountry.value.code.replace('+', '')}-${phone.value.trim()}`,
      vcode: code.value.trim(),
      type: 'mobile',
      source: 'register',
      invite_code: inviteCode.value.trim(),
    })
    if (res.code === 0 && res.data) {
      userStore.saveLogin(res.data.user_id || 0, res.data.token || '')
      resetTokenExpiredFlag()
      await userStore.fetchAfterLogin()
      router.replace('/home')
    }
  } finally {
    registering.value = false
  }
}
</script>

<template>
  <div class="register-screen">
    <div class="register-content">
      <h1>{{ t('create_account') }}</h1>
      <p class="subtitle">{{ t('global_arbitrage_journey') }}</p>

      <label class="field-label">{{ t('invite_code') }}</label>
      <div class="input-box">
        <svg viewBox="0 0 24 24" fill="none" width="20" height="20" class="input-icon">
          <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2" stroke="#3DDC97" stroke-width="1.8"/>
          <circle cx="9" cy="7" r="4" stroke="#3DDC97" stroke-width="1.8"/>
          <path d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" stroke="#3DDC97" stroke-width="1.8"/>
        </svg>
        <input v-model="inviteCode" type="text" />
      </div>

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

      <label class="checkbox-row" @click="agreed = !agreed">
        <span class="checkbox" :class="{ checked: agreed }">
          <svg v-if="agreed" viewBox="0 0 16 16" width="12" height="12"><path d="M3 8l3 3 7-7" stroke="#0A0E14" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
        </span>
        <span>{{ t('agree_terms') }}</span>
      </label>

      <button class="register-btn" :class="{ loading: registering }" :disabled="registering" @click="handleRegister">
        <span v-if="registering" class="btn-spinner"></span>
        {{ registering ? '' : t('register') }}
      </button>
    </div>

    <div class="register-footer">
      <span>{{ t('have_account') }}</span>
      <router-link to="/login" class="link">{{ t('login_now') }}</router-link>
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

.register-screen {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  background: $bg;
  padding: 0 24px;
}

.register-content {
  flex: 1;
  padding-top: 24px;

  h1 {
    font-size: 25px;
    font-weight: 700;
    color: white;
    margin: 0 0 6px;
  }

  .subtitle {
    font-size: 14px;
    color: $muted;
    margin: 0 0 40px;
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
  padding: 0 16px;
  background: $elevated;
  border: 1px solid $border;
  border-radius: 12px;
  margin-bottom: 16px;

  .input-icon { flex-shrink: 0; margin-right: 12px; }

  input {
    flex: 1;
    padding: 16px 0;
    background: none;
    border: none;
    color: white;
    font-size: 15px;
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

.checkbox-row {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  cursor: pointer;
  margin-top: 2px;
  font-size: 13px;
  color: $muted;
  line-height: 1.4;

  .checkbox {
    flex-shrink: 0;
    width: 20px;
    height: 20px;
    border-radius: 4px;
    border: 1px solid $dim;
    display: grid;
    place-items: center;

    &.checked {
      background: $green;
      border-color: $green;
    }
  }
}

.register-btn {
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

.register-footer {
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
