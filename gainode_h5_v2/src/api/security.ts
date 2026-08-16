/**
 * Security 领域客户端 + DTO —— 绑定 OpenAPI user.yaml（S02-P02 APPROVED）。
 *
 * 只暴露【只读端点】：security_profile / sessions / login_audit。
 * 写操作（MFA enrollment setup/confirm/disable、session revoke）后端 fail-closed
 * （无 Active Release 时 503 DEPENDENCY_UNAVAILABLE），前端不得开放真实提交，
 * 故本文件不提供对应写方法。
 *
 * 契约缺口（RECORD 已登记）：
 * - S03-P02-SEC-LOGIN-AUDIT：LoginAudit source 未裁决 → 后端返回 UNAVAILABLE，前端只读空态/受限。
 * - S03-P02-SEC-MFA-WRITE：MFA enrollment setup/confirm/disable 写操作 fail-closed。
 * - S03-P02-SEC-REVOKE：session revoke 写操作 fail-closed。
 */
import { get } from './http'
import type { Envelope } from './types'

/** user.yaml#/SecurityProfile */
export interface SecurityProfile {
  user_id: string
  mfa_enrolled_methods: string[]
  mfa_required_actions?: string[]
  login_history_window?: string | null
  suspicious_flags?: string[] | null
  last_password_change?: number | null
  last_security_review?: number | null
  policy_version?: string | null
}

/** user.yaml#/SessionDevice */
export interface SessionDevice {
  session_id: string
  device_fingerprint?: string | null
  os?: string | null
  browser?: string | null
  ip?: string | null
  location_region?: string | null
  last_active_at?: number | null
  is_current: boolean
  revocable: boolean
}

/** user.yaml#/LoginAudit.outcome（source 未裁决 → UNAVAILABLE） */
export type LoginAuditOutcome = 'success' | 'failure' | 'mfa_required'

/** user.yaml#/LoginAudit */
export interface LoginAudit {
  audit_id: string
  user_id: string
  event_type: string
  ip_address?: string | null
  device_fingerprint?: string | null
  outcome: LoginAuditOutcome
  failure_reason_code?: string | null
  challenge_type?: string | null
  created_at: number
}

export const securityApi = {
  /** 安全画像（operationId: user_security_profile） */
  securityProfile: (): Promise<Envelope<SecurityProfile>> =>
    get<SecurityProfile>('/api/v1/me/security-profile'),

  /** 已登录会话/设备列表（operationId: auth_me_sessions） */
  sessions: (): Promise<Envelope<{ sessions: SessionDevice[] }>> =>
    get<{ sessions: SessionDevice[] }>('/api/v1/me/sessions'),

  /** 登录审计（operationId: user_login_audit，source 未裁决 → UNAVAILABLE） */
  loginAudit: (): Promise<Envelope<LoginAudit>> =>
    get<LoginAudit>('/api/v1/me/login-audit'),
}
