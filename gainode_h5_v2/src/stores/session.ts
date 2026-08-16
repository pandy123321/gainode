import { defineStore } from 'pinia'
import {
  setAccessToken as httpSetAccessToken,
  setRefreshToken as httpSetRefreshToken,
} from '../api/http'

export const useSessionStore = defineStore('session', {
  state: () => ({
    accessToken: null as string | null,
    refreshToken: null as string | null,
    locale: 'zh-CN',
    userId: null as string | null,
    biometricEnabled: false,
  }),
  getters: {
    isAuthenticated: (state) => Boolean(state.accessToken),
  },
  actions: {
    setTokens(access: string | null, refresh: string | null) {
      this.accessToken = access
      this.refreshToken = refresh
      httpSetAccessToken(access)
      httpSetRefreshToken(refresh)
    },
    setLocale(locale: string) {
      this.locale = locale
    },
    setSecurityPrefs(prefs: { biometricEnabled?: boolean }) {
      if (prefs.biometricEnabled !== undefined) this.biometricEnabled = prefs.biometricEnabled
    },
    clear() {
      this.setTokens(null, null)
      this.userId = null
    },
  },
  // 仅持久化 token 引用 + 安全偏好；不持久化 secret 或权威余额
  persist: {
    pick: ['accessToken', 'refreshToken', 'locale', 'biometricEnabled'],
  },
})
