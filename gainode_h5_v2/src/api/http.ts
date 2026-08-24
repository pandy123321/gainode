/**
 * V2 HTTP 客户端 —— 六请求头注入 + auth refresh single-flight + RESULT_UNKNOWN。
 * 对齐 05 §1（写操作幂等/乐观锁）、05 §7（16 项错误码）、05 §10（数据新鲜度）。
 */
import axios, {
  AxiosError,
  AxiosHeaders,
  type AxiosInstance,
  type AxiosRequestConfig,
  type AxiosResponse,
} from 'axios'
import { ApiError, UnknownResultError, RESULT_UNKNOWN } from './types'
import type { ApiErrorBody, Envelope } from './types'
import { generateIdempotencyKey, generateRequestId, nowUnixSeconds } from '../utils/request-id'

const WRITE_METHODS = new Set(['post', 'put', 'patch', 'delete'])

// ---- 令牌/语言持有器（由 session store 写入，避免与 store 循环依赖） ----
let accessToken: string | null = null
let refreshToken: string | null = null
let languageGetter: () => string = () => 'zh-CN'
let refreshUrl = '/api/v1/auth/refresh'

export function setAccessToken(token: string | null): void {
  accessToken = token
}
export function getAccessToken(): string | null {
  return accessToken
}
export function setRefreshToken(token: string | null): void {
  refreshToken = token
}
export function setLanguageGetter(fn: () => string): void {
  languageGetter = fn
}
export function setRefreshUrl(url: string): void {
  refreshUrl = url
}

export interface RequestOptions {
  /** 写操作幂等键（缺失时自动生成）；重复提交须复用同一键 */
  idempotencyKey?: string
  /** object_version 乐观锁 */
  ifMatch?: string
  /** 请求追踪 ID（缺失时生成） */
  requestId?: string
}

interface GainodeRequestConfig extends AxiosRequestConfig {
  idempotencyKey?: string
  ifMatch?: string
  requestId?: string
  _retry?: boolean
}

const instance: AxiosInstance = axios.create({
  baseURL: import.meta.env?.VITE_API_BASE_URL ?? '',
  timeout: 10000,
  headers: { 'Content-Type': 'application/json' },
})

// ---- 请求拦截器：注入六请求头 ----
instance.interceptors.request.use((config) => {
  const cfg = config as GainodeRequestConfig
  const headers = AxiosHeaders.from(config.headers)

  if (accessToken) headers.set('Authorization', `Bearer ${accessToken}`)
  headers.set('Accept-Language', languageGetter())
  headers.set('X-Request-Id', cfg.requestId ?? generateRequestId())
  headers.set('X-Timestamp', nowUnixSeconds())

  if (cfg.method && WRITE_METHODS.has(cfg.method)) {
    const key = cfg.idempotencyKey ?? generateIdempotencyKey()
    cfg.idempotencyKey = key
    headers.set('Idempotency-Key', key)
  }
  if (cfg.ifMatch) headers.set('If-Match', cfg.ifMatch)

  config.headers = headers
  return config
})

// ---- 错误体判定 ----
function isErrorEnvelope(body: unknown): body is ApiErrorBody {
  return (
    typeof body === 'object' &&
    body !== null &&
    typeof (body as Record<string, unknown>).result_code === 'string'
  )
}

// ---- auth refresh single-flight ----
let refreshPromise: Promise<string | null> | null = null

async function doRefresh(): Promise<string | null> {
  if (!refreshToken) return null
  const raw = axios.create({ timeout: 10000 })
  // H5-08：refresh 端点同样携带基础请求头（无 Authorization，令牌已失效）
  const resp = await raw.post<{ data: { access_token: string } }>(
    refreshUrl,
    { refresh_token: refreshToken },
    {
      headers: {
        'Accept-Language': languageGetter(),
        'X-Request-Id': generateRequestId(),
        'X-Timestamp': nowUnixSeconds(),
      },
    },
  )
  const token = resp.data?.data?.access_token ?? null
  if (token) accessToken = token
  return token
}

function singleFlightRefresh(): Promise<string | null> {
  if (!refreshPromise) {
    refreshPromise = doRefresh().finally(() => {
      refreshPromise = null
    })
  }
  return refreshPromise
}

// ---- 响应拦截器 ----
instance.interceptors.response.use(
  (response: AxiosResponse) => {
    const body = response.data as Record<string, unknown>
    // 202 RESULT_UNKNOWN 是唯一以 2xx 返回的“错误”状态；
    // 成功写操作（async/processing）也会带 result_code，故仅特殊处理 RESULT_UNKNOWN。
    if (body?.result_code === RESULT_UNKNOWN) {
      const idem = (response.config as GainodeRequestConfig).idempotencyKey ?? ''
      throw new UnknownResultError({
        idempotency_key: idem,
        request_id: (body.request_id as string) ?? null,
      })
    }
    return response
  },
  async (error: AxiosError) => {
    const cfg = error.config as GainodeRequestConfig | undefined
    const body = error.response?.data as Record<string, unknown> | undefined

    if (isErrorEnvelope(body)) {
      // 401 未认证 → single-flight 刷新后重试一次
      if (body.result_code === 'AUTH_UNAUTHENTICATED' && cfg && !cfg._retry && refreshToken) {
        cfg._retry = true
        const newToken = await singleFlightRefresh()
        if (newToken) {
          cfg.headers = AxiosHeaders.from(cfg.headers as AxiosHeaders)
          ;(cfg.headers as AxiosHeaders).set('Authorization', `Bearer ${newToken}`)
          return instance(cfg)
        }
      }
      if (body.result_code === RESULT_UNKNOWN) {
        throw new UnknownResultError({
          idempotency_key: cfg?.idempotencyKey ?? '',
          request_id: body.request_id,
        })
      }
      throw new ApiError(body)
    }

    // 写请求超时/无响应 → 结果未知，不得自动重 POST
    if (cfg && cfg.method && WRITE_METHODS.has(cfg.method)) {
      throw new UnknownResultError({ idempotency_key: cfg.idempotencyKey ?? '', request_id: null })
    }
    throw new ApiError({
      request_id: (body?.request_id as string) ?? '',
      result_code: 'INTERNAL_ERROR',
      result_message: error.message || '网络请求失败',
      http_status: error.response?.status ?? 0,
      details: {},
    })
  },
)

export async function request<T>(
  config: GainodeRequestConfig,
): Promise<Envelope<T>> {
  const resp = await instance.request<Envelope<T>>(config)
  return resp.data
}

export function get<T>(url: string, config?: GainodeRequestConfig): Promise<Envelope<T>> {
  return request<T>({ ...config, method: 'get', url })
}

export function post<T>(
  url: string,
  data?: unknown,
  config?: GainodeRequestConfig,
): Promise<Envelope<T>> {
  return request<T>({ ...config, method: 'post', url, data })
}

export function put<T>(
  url: string,
  data?: unknown,
  config?: GainodeRequestConfig,
): Promise<Envelope<T>> {
  return request<T>({ ...config, method: 'put', url, data })
}

export function patch<T>(
  url: string,
  data?: unknown,
  config?: GainodeRequestConfig,
): Promise<Envelope<T>> {
  return request<T>({ ...config, method: 'patch', url, data })
}

export function del<T>(url: string, config?: GainodeRequestConfig): Promise<Envelope<T>> {
  return request<T>({ ...config, method: 'delete', url })
}

export default instance
