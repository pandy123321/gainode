/**
 * 认证流程上下文（跨页传递 account/source/purpose/session_id）。
 * 不持久化：OTP challenge / MFA session 属敏感安全上下文，禁落 localStorage。
 */
import { defineStore } from 'pinia'
import type { AccountType, OtpSource } from '../api/auth'

export type AuthPurpose = 'login' | 'register' | 'recovery'

export const useAuthFlowStore = defineStore('authFlow', {
  state: () => ({
    account: '',
    accountType: 'email' as AccountType,
    source: 'login' as OtpSource,
    purpose: 'login' as AuthPurpose,
    sessionId: null as string | null,
    maskedAccount: '',
  }),
  actions: {
    setContext(payload: {
      account: string
      accountType?: AccountType
      source?: OtpSource
      purpose?: AuthPurpose
      sessionId?: string | null
      maskedAccount?: string
    }) {
      this.account = payload.account
      if (payload.accountType) this.accountType = payload.accountType
      if (payload.source) this.source = payload.source
      if (payload.purpose) this.purpose = payload.purpose
      this.sessionId = payload.sessionId ?? null
      this.maskedAccount = payload.maskedAccount ?? ''
    },
    reset() {
      this.account = ''
      this.accountType = 'email'
      this.source = 'login'
      this.purpose = 'login'
      this.sessionId = null
      this.maskedAccount = ''
    },
  },
})
