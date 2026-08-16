import { reactive } from 'vue'
import { UserApi } from '../api/services'

export interface UserInfo {
  user_id?: number
  user_no?: string
  nickname?: string
  account?: string
  avatar?: string
  [key: string]: any
}

interface UserState {
  loggedIn: boolean
  userInfo: UserInfo
  wallets: any[]
}

const state = reactive<UserState>({
  loggedIn: false,
  userInfo: {},
  wallets: [],
})

function getToken(): string {
  return localStorage.getItem('auth_token') || ''
}

function isLoggedIn(): boolean {
  return !!getToken()
}

function saveLogin(userId: number, token: string) {
  localStorage.setItem('auth_token', token)
  localStorage.setItem('user_id', String(userId))
}

function clear() {
  localStorage.removeItem('auth_token')
  localStorage.removeItem('user_id')
  localStorage.removeItem('user_info')
  localStorage.removeItem('wallet_list')
  state.loggedIn = false
  state.userInfo = {}
  state.wallets = []
}

function loadFromStorage() {
  state.loggedIn = isLoggedIn()
  if (state.loggedIn) {
    try {
      const info = localStorage.getItem('user_info')
      if (info) state.userInfo = JSON.parse(info)
      const wallets = localStorage.getItem('wallet_list')
      if (wallets) state.wallets = JSON.parse(wallets)
    } catch { /* ignore */ }
  }
}

async function fetchAfterLogin(): Promise<boolean> {
  let success = false
  const userRes = await UserApi.getUserInfo()
  if (userRes.code === 0 && userRes.data) {
    state.userInfo = userRes.data
    localStorage.setItem('user_info', JSON.stringify(userRes.data))
    success = true
  }
  const walletRes = await UserApi.getWalletList()
  if (walletRes.code === 0 && walletRes.data) {
    const data = walletRes.data
    state.wallets = Array.isArray(data) ? data : [data]
    localStorage.setItem('wallet_list', JSON.stringify(state.wallets))
    success = true
  }
  if (success) state.loggedIn = true
  return success
}

async function logout() {
  await UserApi.logout()
  clear()
}

function getAvailableBalance(): number {
  if (!state.wallets.length) return 0
  const w = state.wallets[0]
  if (!w) return 0
  const funding = w['Funding']
  if (funding && typeof funding === 'object') {
    return parseFloat(funding['available'] || '0') || 0
  }
  return 0
}

export function useUserStore() {
  return {
    state,
    getToken,
    isLoggedIn,
    saveLogin,
    clear,
    loadFromStorage,
    fetchAfterLogin,
    logout,
    getAvailableBalance,
  }
}
