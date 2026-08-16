/**
 * V2 统一响应信封类型 —— 对齐后端 library\response\Envelope + 05 §1/§7/§10。
 */

/** 05 §7 统一错误分类（16 项字符串码） */
export type ResultCode =
  | 'VALIDATION_ERROR'
  | 'AUTH_UNAUTHENTICATED'
  | 'AUTH_FORBIDDEN'
  | 'KYC_REQUIRED'
  | 'POLICY_DENIED'
  | 'FEATURE_CLOSED'
  | 'CONSENT_VERSION_MISMATCH'
  | 'IDEMPOTENCY_CONFLICT'
  | 'OBJECT_VERSION_CONFLICT'
  | 'QUOTE_EXPIRED'
  | 'INSUFFICIENT_APT'
  | 'INSUFFICIENT_POWER'
  | 'MARKET_LOCKED'
  | 'DEPENDENCY_UNAVAILABLE'
  | 'RESULT_UNKNOWN'
  | 'INTERNAL_ERROR'

export const RESULT_UNKNOWN: ResultCode = 'RESULT_UNKNOWN'

export type DataStatus = 'REALTIME' | 'NEAR_REALTIME' | 'STALE' | 'UNAVAILABLE'
export type SourceStatus = 'OK' | 'DEGRADED' | 'UNAVAILABLE'

/** 数据新鲜度契约 8 字段（05 §10） */
export interface FreshnessMeta {
  data_status: DataStatus
  as_of: number | null
  updated_at: number | null
  next_refresh_at: number | null
  refresh_hint: string | null
  stale_after: number | null
  snapshot_id: string | null
  source_status: SourceStatus
}

/** 写操作最少返回字段（05 §1） */
export interface WriteMeta {
  idempotency_key?: string
  object_type?: string
  object_id?: string
  status?: string
  result_code?: string
  result_message?: string
  next_action?: string
  rule_version?: string
  parameter_release_id?: string
  policy_version?: string
  approval_id?: string | null
  audit_event_id?: string
}

/** 成功信封：request_id + data + 新鲜度 + 写操作元数据 */
export interface Envelope<T> extends FreshnessMeta, Partial<WriteMeta> {
  request_id: string
  data: T
}

/** 错误信封 */
export interface ApiErrorBody {
  request_id: string
  result_code: ResultCode
  result_message: string
  http_status: number
  details: Record<string, unknown>
}

/** RESULT_UNKNOWN（202）超时/未知结果 —— 不得重试创建，需用原 Idempotency-Key 查询 */
export interface UnknownResultInfo {
  idempotency_key: string
  request_id: string | null
}

export class ApiError extends Error {
  readonly result_code: ResultCode
  readonly http_status: number
  readonly request_id: string
  readonly details: Record<string, unknown>

  constructor(body: ApiErrorBody) {
    super(body.result_message)
    this.name = 'ApiError'
    this.result_code = body.result_code
    this.http_status = body.http_status
    this.request_id = body.request_id
    this.details = body.details
  }

  get isUnknownResult(): boolean {
    return this.result_code === RESULT_UNKNOWN
  }

  get isAuthError(): boolean {
    return this.result_code === 'AUTH_UNAUTHENTICATED'
  }
}

export class UnknownResultError extends Error {
  readonly idempotency_key: string
  readonly request_id: string | null

  constructor(info: UnknownResultInfo) {
    super('写请求结果未知，请查询原请求终态')
    this.name = 'UnknownResultError'
    this.idempotency_key = info.idempotency_key
    this.request_id = info.request_id
  }
}
