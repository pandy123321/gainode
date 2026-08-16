<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '../../stores/user'
import { UserApi } from '../../api/services'
import { showToast } from '../../utils/toast'
import { t } from '../../i18n'
import ConfirmDialog from '../../components/ConfirmDialog.vue'

const router = useRouter()
const userStore = useUserStore()
const loggingOut = ref(false)
const showLogoutDialog = ref(false)
const giftCode = ref('')
const redeeming = ref(false)
userStore.loadFromStorage()
if (userStore.state.loggedIn) userStore.fetchAfterLogin()

function getBalance() {
  const w = userStore.state.wallets[0]
  if (!w) return { total: 0, available: 0, earnings: 0 }
  const funding = w['Funding'] || {}
  const arbitrage = w['Arbitrage'] || {}
  const balance = parseFloat(funding.balance || '0') || 0
  const frozen = parseFloat(funding.frozen || '0') || 0
  const arbBalance = parseFloat(arbitrage.balance || '0') || 0
  const arbAvailable = parseFloat(arbitrage.available || '0') || 0
  return {
    total: balance + arbBalance,
    available: balance - frozen + arbBalance,
    earnings: arbAvailable,
  }
}

function handleLogout() {
  if (loggingOut.value) return
  showLogoutDialog.value = true
}

async function confirmLogout() {
  if (loggingOut.value) return
  loggingOut.value = true
  try {
    await userStore.logout()
    router.replace('/login')
  } finally {
    loggingOut.value = false
    showLogoutDialog.value = false
  }
}

function copyUid() {
  const uid = userStore.state.userInfo.user_no || ''
  if (uid) {
    navigator.clipboard.writeText(uid)
    showToast(t('copy_success'))
  }
}

async function handleRedeem() {
  if (redeeming.value) return
  const code = giftCode.value.trim()
  if (!code) {
    showToast(t('enter_gift_code'))
    return
  }
  redeeming.value = true
  try {
    const res = await UserApi.receivePacket(code)
    if (res.code === 0) {
      showToast(t('redeem_success'))
      giftCode.value = ''
      await userStore.fetchAfterLogin()
    }
  } finally {
    redeeming.value = false
  }
}
</script>

<template>
  <div class="my-screen">
    <!-- Profile Card -->
    <div class="profile-card" @click="router.push('/profile-edit')">
      <div class="avatar-wrap">
        <img :src="userStore.state.userInfo.avatar || '/images/robotAvatar1.png'" alt="" />
      </div>
      <div class="profile-info">
        <strong>{{
          userStore.state.userInfo.nickname || userStore.state.userInfo.account || t('user')
        }}</strong>
        <div class="uid-row" v-if="userStore.state.userInfo.user_no">
          <span>{{ t('uid_label') }}: {{ userStore.state.userInfo.user_no }}</span>
          <button class="copy-icon" @click.stop="copyUid">
            <svg viewBox="0 0 20 20" fill="none" width="14" height="14">
              <rect x="6.5" y="6.5" width="9" height="9" rx="2" stroke="currentColor" />
              <path
                d="M13.5 6.5v-1a2 2 0 00-2-2h-6a2 2 0 00-2 2v6a2 2 0 002 2h1"
                stroke="currentColor"
              />
            </svg>
          </button>
        </div>
      </div>
      <svg viewBox="0 0 24 24" fill="none" width="20" height="20" class="chevron">
        <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
      </svg>
    </div>

    <!-- Balance Card -->
    <div class="balance-card">
      <span class="balance-label">{{ t('my_balance') }}</span>
      <strong class="balance-total">{{ getBalance().total.toFixed(2) }}</strong>
      <div class="balance-row">
        <div class="balance-col">
          <span>{{ t('available') }}</span>
          <strong>{{ getBalance().available.toFixed(2) }} USDT</strong>
          <small>{{ t('deposit_activate') }}</small>
        </div>
        <div class="balance-col">
          <span>{{ t('earnings') }}</span>
          <strong>{{ getBalance().earnings.toFixed(2) }} USDT</strong>
          <small>{{ t('withdrawable') }}</small>
        </div>
      </div>
      <div class="balance-actions">
        <button class="btn-primary" @click="router.push('/deposit')">{{ t('deposit') }}</button>
        <button class="btn-outline" @click="router.push('/withdraw')">{{ t('withdraw') }}</button>
      </div>
    </div>

    <!-- Gift Redemption -->
    <div class="gift-card">
      <strong>{{ t('gift_redemption') }}</strong>
      <div class="gift-row">
        <input class="gift-input" v-model="giftCode" :placeholder="t('enter_gift_code')" />
        <button class="redeem-btn" :class="{ loading: redeeming }" :disabled="redeeming" @click="handleRedeem">
          <span class="btn-spinner"></span>
          <span class="btn-text">{{ t('redeem_now') }}</span>
        </button>
      </div>
    </div>

    <!-- Menu -->
    <div class="menu-list">
      <div class="menu-item" @click="router.push('/ledger')">
        <div class="menu-icon">
          <svg viewBox="0 0 24 24" fill="none" width="20" height="20">
            <path
              d="M4 4h16v16H4V4zm4 4h8m-8 4h8m-8 4h4"
              stroke="currentColor"
              stroke-width="1.8"
              stroke-linecap="round"
            />
          </svg>
        </div>
        <span>{{ t('transaction_history') }}</span>
        <svg viewBox="0 0 24 24" fill="none" width="16" height="16" class="arrow">
          <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
        </svg>
      </div>
      <div class="menu-item" @click="router.push('/redemption-records')">
        <div class="menu-icon">
          <svg viewBox="0 0 24 24" fill="none" width="20" height="20">
            <rect
              x="3"
              y="6"
              width="18"
              height="14"
              rx="2"
              stroke="currentColor"
              stroke-width="1.8"
            />
            <path
              d="M3 10h18M8 2v4m8-4v4"
              stroke="currentColor"
              stroke-width="1.8"
              stroke-linecap="round"
            />
          </svg>
        </div>
        <span>{{ t('redemption_records') }}</span>
        <svg viewBox="0 0 24 24" fill="none" width="16" height="16" class="arrow">
          <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
        </svg>
      </div>
      <div class="menu-item" @click="router.push('/profile-edit')">
        <div class="menu-icon">
          <svg viewBox="0 0 24 24" fill="none" width="20" height="20">
            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.8" />
            <path
              d="M12 2v2m0 16v2m8.66-15.34l-1.42 1.42m-12.48 12.48l-1.42 1.42m14.84 0l-1.42-1.42M5.76 5.76L4.34 4.34"
              stroke="currentColor"
              stroke-width="1.8"
              stroke-linecap="round"
            />
          </svg>
        </div>
        <span>{{ t('account_settings') }}</span>
        <svg viewBox="0 0 24 24" fill="none" width="16" height="16" class="arrow">
          <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
        </svg>
      </div>
      <div class="menu-item" @click="router.push('/help')">
        <div class="menu-icon">
          <svg viewBox="0 0 24 24" fill="none" width="20" height="20">
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8" />
            <path
              d="M9 9a3 3 0 015.12 2.12c0 1.5-2.12 2.38-2.12 2.38M12 17h.01"
              stroke="currentColor"
              stroke-width="1.8"
              stroke-linecap="round"
            />
          </svg>
        </div>
        <span>{{ t('help_center') }}</span>
        <svg viewBox="0 0 24 24" fill="none" width="16" height="16" class="arrow">
          <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
        </svg>
      </div>
      <div class="menu-item logout" :class="{ disabled: loggingOut }" @click="handleLogout">
        <div class="menu-icon">
          <svg viewBox="0 0 24 24" fill="none" width="20" height="20">
            <path
              d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4m7 14l5-5-5-5m5 5H9"
              stroke="currentColor"
              stroke-width="1.8"
              stroke-linecap="round"
              stroke-linejoin="round"
            />
          </svg>
        </div>
        <span>{{ t('logout_title') }}</span>
        <svg viewBox="0 0 24 24" fill="none" width="16" height="16" class="arrow">
          <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
        </svg>
      </div>
    </div>

    <ConfirmDialog
      v-model:visible="showLogoutDialog"
      :title="t('logout_title')"
      :message="t('logout_confirm_content')"
      :loading="loggingOut"
      @confirm="confirmLogout"
    />
  </div>
</template>

<style scoped lang="scss">
$elevated: #0e1620;
$border: #1e2830;
$green: #3ddc97;
$muted: #8a9cb0;
$input: #12181f;

.my-screen {
  padding: 16px 16px 100px;
}

.profile-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px;
  background: $elevated;
  border-radius: 12px;
  border: 1px solid $border;
  cursor: pointer;
  margin-bottom: 16px;

  .avatar-wrap {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    overflow: hidden;
    flex-shrink: 0;
    img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
  }
  .profile-info {
    flex: 1;
    min-width: 0;
    strong {
      display: block;
      font-size: 18px;
      color: white;
      font-weight: 700;
    }
    .uid-row {
      display: flex;
      align-items: center;
      gap: 4px;
      margin-top: 4px;
      span {
        font-size: 12px;
        color: $muted;
      }
      .copy-icon {
        background: none;
        border: none;
        color: $muted;
        cursor: pointer;
      }
    }
  }
  .chevron {
    color: $muted;
    flex-shrink: 0;
  }
}

.balance-card {
  padding: 12px;
  background: $elevated;
  border-radius: 12px;
  border: 1px solid $border;
  margin-bottom: 16px;

  .balance-label {
    font-size: 13px;
    color: $muted;
  }
  .balance-total {
    display: block;
    font-size: 32px;
    font-weight: 700;
    color: $green;
    margin: 8px 0;
  }

  .balance-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 16px;
    .balance-col {
      span {
        display: block;
        font-size: 12px;
        color: $muted;
      }
      strong {
        display: block;
        font-size: 15px;
        font-weight: 700;
        color: white;
        margin: 4px 0 2px;
      }
      small {
        font-size: 12px;
        color: #4a5568;
      }
    }
  }

  .balance-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    .btn-primary {
      padding: 12px;
      background: $green;
      border: none;
      border-radius: 12px;
      color: #0a0e14;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
    }
    .btn-outline {
      padding: 12px;
      background: none;
      border: 1.5px solid $green;
      border-radius: 12px;
      color: $green;
      font-size: 15px;
      font-weight: 700;
      cursor: pointer;
    }
  }
}

.gift-card {
  padding: 12px;
  background: $elevated;
  border-radius: 12px;
  border: 1px solid $border;
  margin-bottom: 16px;
  strong {
    display: block;
    font-size: 16px;
    color: white;
    font-weight: 700;
    margin-bottom: 12px;
  }
  .gift-row {
    display: flex;
    gap: 12px;
  }
  .gift-input {
    flex: 1;
    padding: 14px;
    background: $input;
    border-radius: 10px;
    border: 1px solid $border;
    font-size: 14px;
    color: white;
    outline: none;
    &::placeholder { color: #4a5568; }
  }
  .redeem-btn {
    position: relative;
    padding: 14px 20px;
    background: $green;
    border: none;
    border-radius: 10px;
    color: #0a0e14;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    white-space: nowrap;
    display: flex; align-items: center; justify-content: center; gap: 6px;
    &:disabled { opacity: 0.6; cursor: not-allowed; }
    &.loading { pointer-events: none; }
    .btn-spinner {
      display: none;
      position: absolute; inset: 0; margin: auto;
      width: 16px; height: 16px;
      border: 2px solid rgba(#0a0e14, 0.3);
      border-top-color: #0a0e14;
      border-radius: 50%;
      animation: spin 0.6s linear infinite;
    }
    &.loading .btn-spinner { display: block; }
    .btn-text { transition: opacity 0.15s; }
    &.loading .btn-text { opacity: 0; }
  }
}

.menu-list {
  display: grid;
  gap: 10px;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 12px;
  background: $elevated;
  border-radius: 12px;
  border: 1px solid $border;
  cursor: pointer;
  &.disabled {
    pointer-events: none;
    opacity: 0.5;
  }

  .menu-icon {
    width: 36px;
    height: 36px;
    display: grid;
    place-items: center;
    background: rgba($green, 0.1);
    border-radius: 8px;
    color: $green;
    flex-shrink: 0;
  }
  span {
    flex: 1;
    font-size: 15px;
    font-weight: 500;
    color: white;
  }
  .arrow {
    color: $muted;
    flex-shrink: 0;
  }
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
