import { beforeEach, describe, expect, it, vi } from 'vitest'

const getMock = vi.hoisted(() => vi.fn())
vi.mock('../../src/api/http', () => ({
  get: getMock,
  post: vi.fn(),
}))

import { securityApi } from '../../src/api/security'
import { userApi } from '../../src/api/user'
import { setActivePinia, createPinia } from 'pinia'
import { useSecurityStore } from '../../src/stores/security'
import { useProfileStore } from '../../src/stores/profile'

beforeEach(() => {
  getMock.mockReset()
  getMock.mockResolvedValue({ request_id: 'r1', data: {} })
  setActivePinia(createPinia())
})

describe('Security 领域 API 绑定路径（只读）', () => {
  it('securityApi.securityProfile → GET /api/v1/me/security-profile', async () => {
    await securityApi.securityProfile()
    expect(getMock).toHaveBeenCalledWith('/api/v1/me/security-profile')
  })

  it('securityApi.sessions → GET /api/v1/me/sessions', async () => {
    await securityApi.sessions()
    expect(getMock).toHaveBeenCalledWith('/api/v1/me/sessions')
  })

  it('securityApi.loginAudit → GET /api/v1/me/login-audit', async () => {
    await securityApi.loginAudit()
    expect(getMock).toHaveBeenCalledWith('/api/v1/me/login-audit')
  })

  it('userApi.me → GET /api/v1/me', async () => {
    await userApi.me()
    expect(getMock).toHaveBeenCalledWith('/api/v1/me')
  })
})

describe('useSecurityStore', () => {
  it('fetchProfile 写入安全画像 + mfaEnrolled', async () => {
    getMock.mockResolvedValue({
      request_id: 'r1',
      data: { user_id: 'u1', mfa_enrolled_methods: ['totp'], suspicious_flags: ['a', 'b'] },
    })
    const store = useSecurityStore()
    await store.fetchProfile()
    expect(store.mfaEnrolled).toEqual(['totp'])
    expect(store.suspiciousCount).toBe(2)
  })

  it('fetchSessions 写入会话 + current/other 分流', async () => {
    getMock.mockResolvedValue({
      request_id: 'r1',
      data: {
        sessions: [
          { session_id: 's1', is_current: true, revocable: false },
          { session_id: 's2', is_current: false, revocable: true },
        ],
      },
    })
    const store = useSecurityStore()
    await store.fetchSessions()
    expect(store.currentSession?.session_id).toBe('s1')
    expect(store.otherSessions.length).toBe(1)
    expect(store.otherSessions[0].session_id).toBe('s2')
  })

  it('fetchProfile 失败 → profileError 写入', async () => {
    getMock.mockRejectedValue(new Error('boom'))
    const store = useSecurityStore()
    await store.fetchProfile()
    expect(store.profileError).toBe('boom')
  })
})

describe('useProfileStore', () => {
  it('fetch 写入用户 + displayName/globalPLevel', async () => {
    getMock.mockResolvedValue({
      request_id: 'r1',
      data: {
        user_id: 'u1',
        status: 'active',
        display_name: 'Ada',
        locale: 'zh-CN',
        global_p_level: 'P2',
        ai_reward_eligibility: true,
        prediction_eligibility: false,
      },
    })
    const store = useProfileStore()
    await store.fetch()
    expect(store.displayName).toBe('Ada')
    expect(store.globalPLevel).toBe('P2')
    expect(store.status).toBe('active')
  })
})
