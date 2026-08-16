/**
 * User 领域客户端 + DTO —— 绑定 OpenAPI components/schemas/user.yaml#/User（S02-P02 APPROVED）。
 * 只做字段透传与类型，不手写第二套字段。
 */
import { get } from './http'
import type { Envelope } from './types'

export type UserStatus = 'active' | 'restricted' | 'suspended' | 'closed'

/** user.yaml#/User（会员最低字段） */
export interface User {
  user_id: string
  status: UserStatus
  display_name: string
  locale: string
  timezone?: string | null
  global_p_level: string
  ai_reward_eligibility: boolean
  prediction_eligibility: boolean
  created_at?: number
  updated_at?: number
}

export const userApi = {
  /** 当前用户信息（operationId: user_me） */
  me: (): Promise<Envelope<User>> => get<User>('/api/v1/me'),
}
