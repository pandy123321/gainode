/**
 * KYC + 资格（FeatureEntitlement）领域客户端 + DTO —— 绑定 OpenAPI
 * components/schemas/kyc.yaml + eligibility.yaml（S02-P02 APPROVED）。
 * 只做字段透传与类型，不手写第二套字段。
 */
import { get, post } from './http'
import type { Envelope } from './types'

/** kyc.yaml#/KycCase 六个 canonical 状态（05 §4，展示映射见 03 M-KYC） */
export type KycStatus =
  | 'not_started'
  | 'pending'
  | 'needs_info'
  | 'approved'
  | 'rejected'
  | 'review'

/** kyc.yaml#/KycCase */
export interface KycCase {
  case_id: string
  user_id: string
  kyc_level: string
  status: KycStatus
  submitted_at?: number | null
  reviewed_at?: number | null
  reviewed_by?: string | null
  reason_code?: string | null
  reason_text_key?: string | null
  next_action?: string | null
  policy_version?: string | null
  rule_version?: string | null
}

/** kyc.yaml#/KycSubmitRequest（附件走后端签发引用，不传直链明文） */
export interface KycSubmitRequest {
  kyc_level: string
  attachment_refs: string[]
  consent_version?: string
}

/** eligibility.yaml#/FeatureEntitlement（allowed_actions 由服务端下发，前端不推导） */
export interface FeatureEntitlement {
  feature_key: string
  allowed: boolean
  reason_code?: string | null
  reason_text_key?: string | null
  next_action?: string | null
  allowed_actions?: string[]
  policy_version?: string | null
  rule_version?: string | null
  expires_at?: number | null
}

/** eligibility.yaml#/EligibilityResponse（global_p / AI / Prediction 三分支互不推导） */
export interface EligibilityResponse {
  user_id: string
  global_p: FeatureEntitlement
  ai: FeatureEntitlement
  prediction: FeatureEntitlement
}

export const kycApi = {
  kycMe: (): Promise<Envelope<KycCase>> => get<KycCase>('/api/v1/me/kyc'),
  kycSubmit: (body: KycSubmitRequest): Promise<Envelope<KycCase>> =>
    post<KycCase>('/api/v1/me/kyc/submit', body),
}

export const eligibilityApi = {
  me: (): Promise<Envelope<EligibilityResponse>> =>
    get<EligibilityResponse>('/api/v1/me/eligibility'),
}
