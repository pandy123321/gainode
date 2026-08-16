<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { WalletApi } from '../../api/services'
import { useUserStore } from '../../stores/user'
import { showToast } from '../../utils/toast'
import { t } from '../../i18n'
import trxImg from '../../assets/images/TRX.png'
import baseImg from '../../assets/images/base.png'
import bnbImg from '../../assets/images/bnb.png'
import ethImg from '../../assets/images/eth.png'
import usdcImg from '../../assets/images/usdc.png'
import usdtImg from '../../assets/images/usdt.png'

const iconMap: Record<string, string> = {
  trx: trxImg, trc20: trxImg, tron: trxImg,
  base: baseImg,
  bnb: bnbImg, bsc: bnbImg, bsc20: bnbImg, bep20: bnbImg, opbnb: bnbImg,
  eth: ethImg, erc20: ethImg, ethereum: ethImg,
  usdc: usdcImg,
  usdt: usdtImg,
}

function tokenIcon(name: string): string | null {
  if (!name) return null
  const key = name.toLowerCase().trim()
  if (iconMap[key]) return iconMap[key]
  if (key.includes('trx') || key.includes('tron')) return trxImg
  if (key.includes('bnb') || key.includes('bsc')) return bnbImg
  if (key.includes('eth') || key.includes('erc')) return ethImg
  if (key.includes('usdt')) return usdtImg
  if (key.includes('usdc')) return usdcImg
  if (key.includes('base')) return baseImg
  return null
}

const router = useRouter()
const userStore = useUserStore()

const address = ref('')
const amount = ref('')
const selectedNetwork = ref('')
const selectedCurrency = ref('')
const wallets = ref<any[]>([])
const tokens = ref<any[]>([])
const config = ref<any>(null)
const showNetworkPicker = ref(false)
const showCurrencyPicker = ref(false)
const submitting = ref(false)

const currentWallet = () => wallets.value.find((w: any) => w.network_name === selectedNetwork.value) || wallets.value[0]
const availableBalance = () => userStore.getAvailableBalance()

onMounted(() => loadData())

async function loadData() {
  const [walletRes, configRes] = await Promise.all([
    WalletApi.getNetworkWallet(),
    WalletApi.getWithdrawConfig(),
  ])
  if (walletRes.code === 0 && walletRes.data) {
    const list = Array.isArray(walletRes.data) ? walletRes.data : []
    wallets.value = list
    if (list.length) {
      selectedNetwork.value = list[0].network_name || ''
      await loadTokens(list[0].network_id)
    }
  }
  if (configRes.code === 0 && configRes.data) config.value = configRes.data
}

async function loadTokens(networkId: number) {
  const res = await WalletApi.getNetworkToken(networkId, 'withdraw')
  if (res.code === 0 && res.data) {
    const list = Array.isArray(res.data) ? res.data : [res.data]
    tokens.value = list
    if (list.length && !selectedCurrency.value) selectedCurrency.value = list[0].symbol || ''
  }
}

async function selectNetwork(w: any) {
  selectedNetwork.value = w.network_name || ''
  selectedCurrency.value = ''
  showNetworkPicker.value = false
  await loadTokens(w.network_id)
}

function withdrawAll() {
  amount.value = availableBalance().toFixed(2)
}

async function handleWithdraw() {
  if (submitting.value) return
  const addr = address.value.trim()
  const amt = parseFloat(amount.value) || 0
  if (!addr || amt <= 0) return
  submitting.value = true
  try {
    const token = tokens.value.find((t: any) => t.symbol === selectedCurrency.value) || {}
    const res = await WalletApi.createWithdraw({
      type: currentWallet()?.network_code || '',
      money: amt,
      currency: token.network_code || '',
      address: addr,
    })
    if (res.code === 0) {
      showToast(t('withdraw_success'))
      address.value = ''
      amount.value = ''
      userStore.fetchAfterLogin()
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <div class="withdraw-screen">
    <header class="screen-header">
      <button class="back-btn" @click="router.back()">
        <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
      <h1>{{ t('withdraw') }}</h1>
      <button class="text-btn" @click="router.push('/withdraw-records')">{{ t('withdraw_records') }}</button>
    </header>

    <div class="screen-content">
      <!-- Network -->
      <label>{{ t('network_select') }}</label>
      <div class="dropdown" @click="showNetworkPicker = true">
        <img v-if="tokenIcon(selectedNetwork)" :src="tokenIcon(selectedNetwork)!" class="token-icon" alt="" />
        <span v-else class="dropdown-icon globe">
          <svg viewBox="0 0 24 24" fill="none" width="18" height="18"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M3 12h18M12 3c2.5 2.8 4 6 4 9s-1.5 6.2-4 9c-2.5-2.8-4-6-4-9s1.5-6.2 4-9z" stroke="currentColor" stroke-width="1.8"/></svg>
        </span>
        <span class="dropdown-value">{{ selectedNetwork || t('select_network') }}</span>
        <svg viewBox="0 0 24 24" fill="none" width="20" height="20" class="chevron"><path d="M8 10l4 4 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>

      <!-- Currency -->
      <label>{{ t('currency') }}</label>
      <div class="dropdown" @click="showCurrencyPicker = true">
        <img v-if="tokenIcon(selectedCurrency)" :src="tokenIcon(selectedCurrency)!" class="token-icon" alt="" />
        <span v-else class="dropdown-icon coin">
          <svg viewBox="0 0 24 24" fill="none" width="18" height="18"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 7v10m-3-7h6m-6 4h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </span>
        <span class="dropdown-value">{{ selectedCurrency || t('select_currency') }}</span>
        <svg viewBox="0 0 24 24" fill="none" width="20" height="20" class="chevron"><path d="M8 10l4 4 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </div>

      <!-- Address -->
      <label>{{ t('withdraw_address') }}</label>
      <div class="input-box">
        <input v-model="address" type="text" :placeholder="t('long_press_paste')" />
        <button class="qr-scan-btn">
          <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><rect x="3" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="1.8"/><rect x="14" y="3" width="7" height="7" rx="1" stroke="currentColor" stroke-width="1.8"/><rect x="3" y="14" width="7" height="7" rx="1" stroke="currentColor" stroke-width="1.8"/><rect x="15" y="15" width="4" height="4" rx="1" stroke="currentColor" stroke-width="1.8"/><path d="M21 21l-3-3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </button>
      </div>

      <!-- Amount -->
      <label>{{ t('withdraw_amount') }}</label>
      <div class="amount-card">
        <div class="amount-input-row">
          <input v-model="amount" type="text" inputmode="decimal" placeholder="0" class="amount-input" />
          <span class="currency-label">USDT</span>
        </div>
        <div class="balance-row">
          <span>{{ t('available_balance_label') }} $ {{ availableBalance().toFixed(2) }}</span>
          <button class="all-btn" @click="withdrawAll">{{ t('withdraw_all') }}</button>
        </div>
      </div>

      <!-- Notice -->
      <div class="notice-card">
        <div class="notice-title">
          <svg viewBox="0 0 24 24" fill="none" width="18" height="18"><circle cx="12" cy="12" r="9" stroke="#FFB800" stroke-width="1.8"/><path d="M12 8v4M12 16h0" stroke="#FFB800" stroke-width="2" stroke-linecap="round"/></svg>
          <span>{{ t('withdraw_notice_title') }}</span>
        </div>
        <p>{{ t('min_withdraw_label') }}: {{ config?.min_money || 0 }} USDT</p>
        <p>{{ t('fee_rate_label') }}: {{ config?.withdraw_rate || config?.withdrawRate || '-' }}%</p>
        <p>{{ t('withdraw_address_warning') }}</p>
        <p v-if="config?.descr">{{ config.descr }}</p>
      </div>
    </div>

    <div class="bottom-action">
      <button class="confirm-btn" :class="{ loading: submitting }" :disabled="submitting" @click="handleWithdraw">
        <span v-if="submitting" class="btn-spinner"></span>
        {{ submitting ? '' : t('confirm_withdraw') }}
      </button>
    </div>

    <!-- Network Picker Modal -->
    <Teleport to="body">
      <div v-if="showNetworkPicker" class="picker-overlay" @click.self="showNetworkPicker = false">
        <div class="picker-sheet">
          <div class="picker-header">
            <strong>{{ t('select_network') }}</strong>
            <button class="close-btn" @click="showNetworkPicker = false">
              <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
          </div>
          <div class="picker-list">
            <button
              v-for="w in wallets"
              :key="w.network_id"
              class="picker-item"
              :class="{ selected: w.network_name === selectedNetwork }"
              @click="selectNetwork(w)"
            >
              <img v-if="tokenIcon(w.network_name)" :src="tokenIcon(w.network_name)!" class="picker-icon" alt="" />
              <span>{{ w.network_name }}</span>
              <svg v-if="w.network_name === selectedNetwork" viewBox="0 0 20 20" fill="none" width="16" height="16"><path d="M4 10l4 4 8-8" stroke="#3DDC97" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Currency Picker Modal -->
    <Teleport to="body">
      <div v-if="showCurrencyPicker" class="picker-overlay" @click.self="showCurrencyPicker = false">
        <div class="picker-sheet">
          <div class="picker-header">
            <strong>{{ t('select_currency') }}</strong>
            <button class="close-btn" @click="showCurrencyPicker = false">
              <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            </button>
          </div>
          <div class="picker-list">
            <button
              v-for="tkn in tokens"
              :key="tkn.symbol"
              class="picker-item"
              :class="{ selected: tkn.symbol === selectedCurrency }"
              @click="selectedCurrency = tkn.symbol; showCurrencyPicker = false"
            >
              <img v-if="tokenIcon(tkn.symbol)" :src="tokenIcon(tkn.symbol)!" class="picker-icon" alt="" />
              <span>{{ tkn.symbol }}</span>
              <svg v-if="tkn.symbol === selectedCurrency" viewBox="0 0 20 20" fill="none" width="16" height="16"><path d="M4 10l4 4 8-8" stroke="#3DDC97" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped lang="scss">
$elevated: #0E1620;
$border: #1E2830;
$green: #3DDC97;
$muted: #8A9CB0;
$input: #12181F;

.withdraw-screen { min-height: 100vh; background: #0A0E14; display: flex; flex-direction: column; }

.screen-header {
  position: sticky; top: 0; z-index: 50;
  display: flex; align-items: center; gap: 12px; padding: 12px 16px;
  background: rgba(14, 22, 32, 0.95); backdrop-filter: blur(10px);
  .back-btn { background: none; border: none; color: white; cursor: pointer; }
  h1 { flex: 1; font-size: 18px; font-weight: 700; color: white; margin: 0; }
  .text-btn { background: none; border: none; color: $green; font-size: 14px; cursor: pointer; }
}

.screen-content {
  flex: 1; padding: 16px; overflow-y: auto;
  label { display: block; font-size: 14px; color: $muted; font-weight: 500; margin-bottom: 10px; }
}

// Dropdown with green-circle icon (matching Flutter buildDropdownButton)
.dropdown {
  display: flex; align-items: center; gap: 12px; padding: 16px;
  background: $elevated; border: 1px solid $border; border-radius: 12px;
  margin-bottom: 20px; color: white; cursor: pointer;

  .dropdown-icon {
    width: 32px; height: 32px; border-radius: 50%;
    background: rgba($green, 0.1); color: $green;
    display: grid; place-items: center; flex-shrink: 0;
  }
  .dropdown-value { flex: 1; font-size: 15px; font-weight: 600; }
  .chevron { color: $muted; flex-shrink: 0; }
  .token-icon { width: 22px; height: 22px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
}

// Address input with QR scan button
.input-box {
  display: flex; align-items: center; gap: 8px; margin-bottom: 20px;
  padding: 4px 4px 4px 16px; background: $elevated;
  border: 1px solid $border; border-radius: 12px;

  input {
    flex: 1; padding: 12px 0; background: none; border: none;
    color: white; font-size: 14px; outline: none;
    &::placeholder { color: #4A5568; }
  }
  .qr-scan-btn {
    width: 40px; height: 40px; display: grid; place-items: center;
    background: none; border: none; color: $muted; cursor: pointer;
    border-radius: 8px;
    &:hover { background: rgba($muted, 0.1); }
  }
}

// Amount card
.amount-card {
  padding: 20px; background: $elevated; border-radius: 14px; border: 1px solid $border; margin-bottom: 20px;

  .amount-input-row {
    display: flex; align-items: flex-end; gap: 8px; min-width: 0;
    .amount-input {
      flex: 1; min-width: 0; background: none; border: none; color: white;
      font-size: 36px; font-weight: 700; line-height: 1.2; outline: none; padding: 0;
      &::placeholder {
        color: #4A5568;
        font-size: 36px;
        font-weight: 700;
      }
    }
    .currency-label { font-size: 16px; color: $muted; padding-bottom: 6px; flex-shrink: 0; }
  }

  .balance-row {
    display: flex; justify-content: space-between; align-items: center; margin-top: 12px;
    span { font-size: 12px; color: $muted; }
    .all-btn { background: none; border: none; color: $green; font-size: 13px; font-weight: 600; cursor: pointer; }
  }
}

// Notice card
.notice-card {
  padding: 16px; background: $elevated; border-radius: 12px; border: 1px solid $border;
  .notice-title {
    display: flex; align-items: center; gap: 8px;
    font-size: 14px; font-weight: 700; color: #FFB800; margin-bottom: 12px;
  }
  p { font-size: 12px; color: $muted; line-height: 1.6; margin: 0 0 6px; }
}

// Bottom action
.bottom-action {
  padding: 12px 16px 24px;
  .confirm-btn {
    display: flex; align-items: center; justify-content: center; gap: 8px;
    width: 100%; padding: 16px; background: linear-gradient(90deg, #26FFBF, #00D98C);
    border: none; border-radius: 12px; color: #0A0E14; font-size: 16px; font-weight: 700;
    cursor: pointer; box-shadow: 0 4px 16px rgba($green, 0.3);
    transition: opacity 0.2s;

    &:disabled { opacity: 0.6; cursor: not-allowed; }

    .btn-spinner {
      width: 20px; height: 20px; border: 2px solid rgba(#0A0E14, 0.3);
      border-top-color: #0A0E14; border-radius: 50%;
      animation: spin 0.6s linear infinite;
    }
  }
}

// Picker Modal
.picker-overlay {
  position: fixed; inset: 0; z-index: 2000;
  display: flex; align-items: flex-end;
  background: rgba(0, 0, 0, 0.5);
}

.picker-sheet {
  width: 100%; max-height: 50vh;
  background: #12181F; border-radius: 20px 20px 0 0;
  display: flex; flex-direction: column;
}

.picker-header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 16px 20px; border-bottom: 1px solid $border;

  strong { font-size: 18px; font-weight: 600; color: white; }
  .close-btn { background: none; border: none; color: $muted; cursor: pointer; }
}

.picker-list {
  flex: 1; overflow-y: auto; padding: 8px 0;

  .picker-item {
    display: flex; align-items: center; gap: 14px;
    width: 100%; padding: 14px 20px;
    background: none; border: none;
    color: white; font-size: 15px; cursor: pointer; text-align: left;

    &:hover { background: $elevated; }
    &.selected { background: $border;
      span { color: $green; }
    }
    span { flex: 1; }
    .picker-icon { width: 22px; height: 22px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
  }
}
@keyframes spin { to { transform: rotate(360deg); } }
</style>
