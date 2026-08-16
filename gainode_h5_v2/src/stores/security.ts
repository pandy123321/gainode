import { defineStore } from 'pinia'
import { securityApi, type SecurityProfile, type SessionDevice } from '../api/security'

interface SecurityState {
  profile: SecurityProfile | null
  sessions: SessionDevice[]
  profileLoading: boolean
  profileError: string | null
  sessionsLoading: boolean
  sessionsError: string | null
}

/** Security 状态 —— 权威只读投影；MFA/revoke 写操作 fail-closed 不提供 */
export const useSecurityStore = defineStore('security', {
  state: (): SecurityState => ({
    profile: null,
    sessions: [],
    profileLoading: false,
    profileError: null,
    sessionsLoading: false,
    sessionsError: null,
  }),
  getters: {
    mfaEnrolled: (s): string[] => s.profile?.mfa_enrolled_methods ?? [],
    suspiciousCount: (s): number => s.profile?.suspicious_flags?.length ?? 0,
    /** 当前会话 + 其余可撤销会话（脱敏展示，不落完整 IP/指纹） */
    currentSession: (s): SessionDevice | null =>
      s.sessions.find((d) => d.is_current) ?? null,
    otherSessions: (s): SessionDevice[] =>
      s.sessions.filter((d) => !d.is_current),
  },
  actions: {
    async fetchProfile() {
      this.profileLoading = true
      this.profileError = null
      try {
        const env = await securityApi.securityProfile()
        this.profile = env.data
      } catch (e) {
        this.profileError = e instanceof Error ? e.message : '安全画像加载失败'
      } finally {
        this.profileLoading = false
      }
    },
    async fetchSessions() {
      this.sessionsLoading = true
      this.sessionsError = null
      try {
        const env = await securityApi.sessions()
        this.sessions = env.data?.sessions ?? []
      } catch (e) {
        this.sessionsError = e instanceof Error ? e.message : '会话列表加载失败'
      } finally {
        this.sessionsLoading = false
      }
    },
    reset() {
      this.profile = null
      this.sessions = []
      this.profileLoading = false
      this.profileError = null
      this.sessionsLoading = false
      this.sessionsError = null
    },
  },
})
