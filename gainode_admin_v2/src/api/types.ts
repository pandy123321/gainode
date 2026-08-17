/**
 * Admin V2 统一响应信封类型 —— 对齐 05 §1/§7/§10 + 04 §12 权限公式。
 * 与 gainode_h5_v2/src/api/types.ts 同构，仅补充 Admin 特有的导出任务 / 数据范围类型。
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

/**
 * 服务端授权输出（04 §12：canonical_role + data_scope + object_state +
 * allowed_actions + risk_policy + SoD）。前端按钮只读 allowed_actions，
 * 禁止按本地金额/等级/KYC 推断可操作性。
 */
export type AllowedActions = string[]

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

  get isObjectVersionConflict(): boolean {
    return this.result_code === 'OBJECT_VERSION_CONFLICT'
  }

  get isAuthError(): boolean {
    return this.result_code === 'AUTH_UNAUTHENTICATED'
  }

  get isForbidden(): boolean {
    return this.result_code === 'AUTH_FORBIDDEN' || this.result_code === 'POLICY_DENIED'
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

/** 异步任务终态（GET /async-jobs/{id}） */
export type AsyncJobStatus = 'pending' | 'processing' | 'completed' | 'failed'

export interface AsyncJob<T = unknown> {
  job_id: string
  status: AsyncJobStatus
  progress?: number | null
  result?: T | null
  error?: { code: string; message: string } | null
}

/** 导出任务（POST /export-tasks） */
export interface ExportTaskResult {
  task_id: string
  status: AsyncJobStatus
  download_url?: string | null
}
