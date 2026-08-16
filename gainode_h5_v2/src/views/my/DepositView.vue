<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { WalletApi } from '../../api/services'
import { showToast } from '../../utils/toast'
import { t } from '../../i18n'
import trxImg from '../../assets/images/TRX.png'
import baseImg from '../../assets/images/base.png'
import bnbImg from '../../assets/images/bnb.png'
import ethImg from '../../assets/images/eth.png'
import usdcImg from '../../assets/images/usdc.png'
import usdtImg from '../../assets/images/usdt.png'

// 网络/币种名称 → 图标映射（大小写不敏感）
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
  // 含有关键词的模糊匹配
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
const selectedNetwork = ref('')
const selectedCurrency = ref('')
const wallets = ref<any[]>([])
const tokens = ref<any[]>([])
const config = ref<any>(null)
const loading = ref(true)
const showNetworkPicker = ref(false)
const showCurrencyPicker = ref(false)

const currentWallet = () => wallets.value.find((w: any) => w.network_name === selectedNetwork.value) || wallets.value[0]

onMounted(() => loadData())

async function loadData() {
  const [configRes, walletRes] = await Promise.all([
    WalletApi.getRechargeConfig(),
    WalletApi.getNetworkWallet(),
  ])
  if (configRes.code === 0 && configRes.data) config.value = configRes.data
  if (walletRes.code === 0 && walletRes.data) {
    const list = Array.isArray(walletRes.data) ? walletRes.data : []
    wallets.value = list
    if (list.length) {
      selectedNetwork.value = list[0].network_name || ''
      await loadTokens(list[0].network_id)
    }
  }
  loading.value = false
}

async function loadTokens(networkId: number) {
  const res = await WalletApi.getNetworkToken(networkId, 'recharge')
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

async function copyAddress() {
  const address = currentWallet()?.wallet_address || ''
  if (!address) return
  try {
    await navigator.clipboard.writeText(address)
    showToast(t('address_copied'))
  } catch { /* ignore */ }
}
</script>

<template>
  <div class="deposit-screen">
    <header class="screen-header">
      <button class="back-btn" @click="router.back()">
        <svg viewBox="0 0 24 24" fill="none" width="20" height="20"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
      <h1>{{ t('deposit_title') }}</h1>
      <button class="text-btn" @click="router.push('/deposit-records')">{{ t('deposit_records') }}</button>
    </header>

    <div class="screen-content">
      <label>{{ t('network_select') }}</label>
      <div class="dropdown" @click="showNetworkPicker = true">
        <img v-if="tokenIcon(selectedNetwork)" :src="tokenIcon(selectedNetwork)!" class="token-icon" alt="" />
        <svg v-else viewBox="0 0 24 24" fill="none" width="18" height="18"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M3 12h18M12 3c2.5 2.8 4 6 4 9s-1.5 6.2-4 9c-2.5-2.8-4-6-4-9s1.5-6.2 4-9z" stroke="currentColor" stroke-width="1.8"/></svg>
        <span>{{ selectedNetwork || t('select_network') }}</span>
        <svg viewBox="0 0 20 20" fill="none" width="16" height="16" class="chevron"><path d="M6 8l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
      </div>

      <label>{{ t('currency') }}</label>
      <div class="dropdown" @click="showCurrencyPicker = true">
        <img v-if="tokenIcon(selectedCurrency)" :src="tokenIcon(selectedCurrency)!" class="token-icon" alt="" />
        <svg v-else viewBox="0 0 24 24" fill="none" width="18" height="18"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="M12 7v10m-3-7h6m-6 4h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        <span>{{ selectedCurrency || t('select_currency') }}</span>
        <svg viewBox="0 0 20 20" fill="none" width="16" height="16" class="chevron"><path d="M6 8l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
      </div>

      <div class="qr-section">
        <div class="qr-box">
          <img
            v-if="currentWallet()?.wallet_address"
            :src="'https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=' + encodeURIComponent(currentWallet()?.wallet_address || '')"
            alt="QR"
            class="qr-img"
          />
          <div v-else class="qr-placeholder">QR</div>
        </div>
        <div class="address-row">
          <span>{{ t('deposit_address') }}</span>
          <small>{{ selectedNetwork }}</small>
        </div>
        <div class="address-box">{{ currentWallet()?.wallet_address || '' }}</div>
        <button class="copy-btn" @click="copyAddress">{{ t('copy') }}</button>
      </div>

      <div class="info-row">
        <span>{{ t('min_deposit') }}</span>
        <strong>{{ config?.min_money || 0 }} USDT</strong>
      </div>

      <div class="notice-card">
        <div class="notice-title">⚠ {{ t('important_notice') }}</div>
        <p>{{ t('deposit_notice_1') }}</p>
        <p>{{ t('deposit_notice_2') }}</p>
        <p>{{ t('deposit_notice_3') }}</p>
      </div>
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

.deposit-screen { min-height: 100vh; background: #0A0E14; }

.screen-header {
  position: sticky;
  top: 0;
  z-index: 50;
  display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: rgba(14, 22, 32, 0.95); backdrop-filter: blur(10px);
  .back-btn { background: none; border: none; color: white; cursor: pointer; }
  h1 { flex: 1; font-size: 18px; font-weight: 700; color: white; margin: 0; }
  .text-btn { background: none; border: none; color: $green; font-size: 14px; cursor: pointer; }
}

.screen-content {
  padding: 16px;
  label { display: block; font-size: 14px; color: $muted; font-weight: 500; margin-bottom: 10px; }
}

.dropdown {
  display: flex; align-items: center; gap: 12px; padding: 16px;
  background: $elevated; border: 1px solid $border; border-radius: 12px;
  margin-bottom: 20px; color: white; cursor: pointer;

  span { flex: 1; font-size: 15px; }
  .chevron { color: $muted; flex-shrink: 0; }
  .token-icon { width: 22px; height: 22px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
}

.qr-section {
  padding: 20px; background: $elevated; border-radius: 16px; border: 1px solid $border; margin-bottom: 20px;

  .qr-box {
    width: 170px; height: 170px; display: flex; justify-content: center; align-items: center;
    padding: 0; margin: 0 auto 16px; background: white; border-radius: 12px; overflow: hidden;
    .qr-img { width: 160px; height: 160px; display: block; }
    .qr-placeholder { width: 160px; height: 160px; display: grid; place-items: center; font-size: 24px; color: #999; border: 2px dashed #ccc; border-radius: 8px; }
  }
  .address-row { display: flex; justify-content: space-between; margin-bottom: 8px;
    span { font-size: 12px; color: $muted; }
    small { font-size: 12px; color: $green; }
  }
  .address-box { padding: 10px 12px; background: $input; border-radius: 8px; font-size: 12px; color: white; font-family: monospace; text-align: center; word-break: break-all; margin-bottom: 14px; }
  .copy-btn { display: block; width: 100%; padding: 14px; background: $green; border: none; border-radius: 12px; color: #0A0E14; font-size: 15px; font-weight: 700; cursor: pointer; }
}

.info-row {
  display: flex; justify-content: space-between; padding: 12px 16px;
  background: $elevated; border-radius: 12px; border: 1px solid $border; margin-bottom: 20px;
  span { font-size: 13px; color: $muted; }
  strong { font-size: 14px; color: white; }
}

.notice-card {
  padding: 16px; background: $elevated; border-radius: 12px; border: 1px solid $border;
  .notice-title { font-size: 14px; font-weight: 700; color: #FFB800; margin-bottom: 12px; }
  p { font-size: 12px; color: $muted; line-height: 1.6; margin: 0 0 8px; padding-left: 12px; position: relative;
    &::before { content: '•'; position: absolute; left: 0; }
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

    .picker-icon { width: 22px; height: 22px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
    span { flex: 1; }
  }
}
</style>
