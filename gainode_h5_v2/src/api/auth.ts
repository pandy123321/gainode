/**
 * Auth 领域客户端 + DTO —— 绑定 OpenAPI components/schemas/auth.yaml（S02-P02 APPROVED）。
 * 只做字段透传与类型，不手写第二套字段；写操作经 http.ts 注入 Idempotency-Key。
 */
import { post } from './http'
import type { Envelope } from './types'

export type AccountType = 'email' | 'mobile'
export type OtpSource = 'login' | 'register' | 'forget' | 'code'

/** auth.yaml#/LoginRequest */
export interface LoginRequest {
  account: string
  password: string
}

/** auth.yaml#/AuthTokenResponse（刷新流程经 http.ts single-flight，不在此暴露 refresh_token） */
export interface AuthTokenResponse {
  token_type: string
  access_token: string
  expires_in: number
  session_id: string
  mfa_required: boolean
  mfa_enrollment?: MfaEnrollment | null
}

/** user.yaml#/MfaEnrollment（最小引用） */
export interface MfaEnrollment {
  enrollment_id: string
  user_id: string
  method_type: 'totp'
  status: 'pending' | 'active' | 'revoked'
  enrolled_at?: number | null
  last_verified_at?: number | null
  backup_codes_active?: boolean
  device_info?: string | null
}

/** auth.yaml#/RegisterRequest */
export interface RegisterRequest {
  account: string
  account_type: AccountType
  consent_version: string
  password?: string
  vcode?: string
  invite_code?: string | null
  nickname?: string | null
  locale?: string
  timezone?: string | null
}

/** register 200 响应：WriteResult + user_id/account */
export interface RegisterResult {
  user_id: string
  account: string
}

/** auth.yaml#/OtpVerifyRequest */
export interface OtpVerifyRequest {
  account: string
  vcode: string
  type?: AccountType
  source?: OtpSource
}

/** auth.yaml#/OtpResendRequest */
export interface OtpResendRequest {
  account: string
  type?: AccountType
  source?: OtpSource
}

/** auth.yaml#/MfaVerifyRequest */
export interface MfaVerifyRequest {
  code: string
  session_id?: string | null
}

/** auth.yaml#/RecoveryRequest */
export interface RecoveryRequest {
  account: string
}

/** auth.yaml#/PasswordResetRequest */
export interface PasswordResetRequest {
  account: string
  vcode: string
  password: string
}

/** 写操作空 data 兜底类型 */
type EmptyData = Record<string, never>

export const authApi = {
  login: (body: LoginRequest): Promise<Envelope<AuthTokenResponse>> =>
    post<AuthTokenResponse>('/api/v1/auth/login', body),
  register: (body: RegisterRequest): Promise<Envelope<RegisterResult>> =>
    post<RegisterResult>('/api/v1/auth/register', body),
  otpVerify: (body: OtpVerifyRequest): Promise<Envelope<EmptyData>> =>
    post<EmptyData>('/api/v1/auth/otp/verify', body),
  otpResend: (body: OtpResendRequest): Promise<Envelope<EmptyData>> =>
    post<EmptyData>('/api/v1/auth/otp/resend', body),
  mfaVerify: (body: MfaVerifyRequest): Promise<Envelope<AuthTokenResponse>> =>
    post<AuthTokenResponse>('/api/v1/auth/mfa/verify', body),
  recovery: (body: RecoveryRequest): Promise<Envelope<EmptyData>> =>
    post<EmptyData>('/api/v1/auth/recovery', body),
  passwordReset: (body: PasswordResetRequest): Promise<Envelope<EmptyData>> =>
    post<EmptyData>('/api/v1/auth/password/reset', body),
  logout: (): Promise<Envelope<EmptyData>> =>
    post<EmptyData>('/api/v1/auth/logout'),
}
