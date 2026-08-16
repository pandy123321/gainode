/**
 * Notice 领域客户端 + DTO —— 绑定 OpenAPI components/schemas/governance.yaml#/Notice（S02-P07）。
 *
 * 注意：C 端路径 /me/notices、/me/notices/{id}/read 尚未冻结（契约缺口 S03-P02-NOTICE-PATH，
 * 见 PAGE_IMPLEMENTATION_RECORD）。本文件按 03 原型 best-effort 绑定，路径冻结后无需改调用层。
 */
import { get, post } from './http'
import type { Envelope } from './types'

export type NoticePriority = 'INFO' | 'WARNING' | 'CRITICAL'
export type ReadState = 'unread' | 'read'

/** governance.yaml#/Notice（只读聚合，read_state 可变，无状态机） */
export interface Notice {
  notice_id: string
  user_id: string
  notice_type: string
  title_key: string
  body_key: string
  priority?: NoticePriority
  related_object_type?: string
  related_object_id?: string
  read_state: ReadState
  content_version?: string
  locale?: string
  expires_at?: number
  object_version?: number
  audit_event_id?: string
}

type EmptyData = Record<string, never>

export const noticeApi = {
  list: (): Promise<Envelope<Notice[]>> => get<Notice[]>('/api/v1/me/notices'),
  read: (noticeId: string): Promise<Envelope<EmptyData>> =>
    post<EmptyData>(`/api/v1/me/notices/${noticeId}/read`),
}
