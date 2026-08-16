import { beforeEach, describe, expect, it, vi } from 'vitest'
import { ApiError } from '../../src/api/types'
import { maskAccount } from '../../src/utils/mask'
import { authErrorMessage } from '../../src/views/auth/authError'

// ---- mock http 模块，验证 authApi 透传 URL 与 body（不重写第二套字段） ----
const postMock = vi.hoisted(() => vi.fn())
vi.mock('../../src/api/http', () => ({
  post: postMock,
}))

import { authApi } from '../../src/api/auth'

beforeEach(() => {
  postMock.mockReset()
  postMock.mockResolvedValue({ request_id: 'r1', data: {} })
})

describe('authApi 绑定 OpenAPI 路径', () => {
  it('login → POST /api/v1/auth/login，透传 account/password', async () => {
    await authApi.login({ account: 'a@b.com', password: 'x' })
    expect(postMock).toHaveBeenCalledWith('/api/v1/auth/login', { account: 'a@b.com', password: 'x' })
  })

  it('register → POST /api/v1/auth/register', async () => {
    await authApi.register({ account: 'a@b.com', account_type: 'email', consent_version: '2026-08-01' })
    expect(postMock).toHaveBeenCalledWith('/api/v1/auth/register', {
      account: 'a@b.com',
      account_type: 'email',
      consent_version: '2026-08-01',
    })
  })

  it('otpVerify → POST /api/v1/auth/otp/verify，source 透传', async () => {
    await authApi.otpVerify({ account: 'a@b.com', vcode: '123456', type: 'email', source: 'register' })
    expect(postMock).toHaveBeenCalledWith('/api/v1/auth/otp/verify', {
      account: 'a@b.com',
      vcode: '123456',
      type: 'email',
      source: 'register',
    })
  })

  it('mfaVerify → POST /api/v1/auth/mfa/verify，携带 session_id', async () => {
    await authApi.mfaVerify({ code: '654321', session_id: 's1' })
    expect(postMock).toHaveBeenCalledWith('/api/v1/auth/mfa/verify', { code: '654321', session_id: 's1' })
  })

  it('passwordReset → POST /api/v1/auth/password/reset', async () => {
    await authApi.passwordReset({ account: 'a@b.com', vcode: '123456', password: 'newpass1' })
    expect(postMock).toHaveBeenCalledWith('/api/v1/auth/password/reset', {
      account: 'a@b.com',
      vcode: '123456',
      password: 'newpass1',
    })
  })

  it('recovery → POST /api/v1/auth/recovery', async () => {
    await authApi.recovery({ account: 'a@b.com' })
    expect(postMock).toHaveBeenCalledWith('/api/v1/auth/recovery', { account: 'a@b.com' })
  })
})

describe('maskAccount 脱敏', () => {
  it('邮箱保留首 2 + 尾 1', () => {
    expect(maskAccount('alice@example.com')).toBe('al***e@example.com')
  })
  it('手机号保留前 3 + 后 4', () => {
    expect(maskAccount('13812345678')).toBe('138****5678')
  })
  it('空串返回空', () => {
    expect(maskAccount('')).toBe('')
  })
})

describe('authErrorMessage 本地化映射（防枚举/禁 raw enum）', () => {
  const err = (code: ApiError['result_code']) =>
    new ApiError({ request_id: 'r1', result_code: code, result_message: code, http_status: 401, details: {} })

  it('AUTH_UNAUTHENTICATED → 统一账号或密码错误', () => {
    expect(authErrorMessage(err('AUTH_UNAUTHENTICATED'))).toBe('账号或密码错误')
  })
  it('POLICY_DENIED → 账号锁定', () => {
    expect(authErrorMessage(err('POLICY_DENIED'))).toBe('账号已锁定，请联系客服')
  })
  it('DEPENDENCY_UNAVAILABLE → 受限', () => {
    expect(authErrorMessage(err('DEPENDENCY_UNAVAILABLE'))).toBe('暂时受限')
  })
  it('非 ApiError → 通用错误', () => {
    expect(authErrorMessage(new Error('x'))).toBe('出错了，请稍后重试')
  })
})
