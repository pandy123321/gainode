import { beforeEach, describe, expect, it } from 'vitest'
import { AxiosError } from 'axios'
import instance, {
  request,
  setAccessToken,
  setLanguageGetter,
  setRefreshToken,
} from '../../src/api/http'
import { ApiError, UnknownResultError } from '../../src/api/types'

let captured: any = null
let adapterResult: { response?: any; error?: any } | null = null

function installAdapter() {
  instance.defaults.adapter = async (config: any) => {
    captured = config
    if (adapterResult?.error) throw adapterResult.error
    return adapterResult?.response
  }
}

function header(config: any, name: string): string {
  const h = config?.headers
  if (h && typeof h.get === 'function') return String(h.get(name) ?? '')
  return String(h?.[name] ?? '')
}

function okEnvelope(data: unknown = { ok: true }) {
  return {
    data: { request_id: 'r1', data, data_status: 'REALTIME' },
    status: 200,
    statusText: 'OK',
    headers: {},
    config: {},
    request: {},
  }
}

beforeEach(() => {
  captured = null
  adapterResult = null
  setAccessToken(null)
  setRefreshToken(null)
  setLanguageGetter(() => 'zh-CN')
  installAdapter()
})

describe('http 六请求头', () => {
  it('注入 Authorization / Accept-Language / X-Request-Id / X-Timestamp', async () => {
    setAccessToken('tok123')
    adapterResult = { response: okEnvelope() }
    await request({ method: 'get', url: '/api/v1/ping' })
    expect(header(captured, 'Authorization')).toBe('Bearer tok123')
    expect(header(captured, 'Accept-Language')).toBe('zh-CN')
    expect(header(captured, 'X-Request-Id')).toBeTruthy()
    expect(header(captured, 'X-Timestamp')).toBeTruthy()
  })

  it('写请求注入 Idempotency-Key；可选 If-Match', async () => {
    adapterResult = { response: okEnvelope() }
    await request({ method: 'post', url: '/api/v1/orders', data: {}, ifMatch: 'v3' })
    expect(header(captured, 'Idempotency-Key')).toBeTruthy()
    expect(header(captured, 'If-Match')).toBe('v3')
  })

  it('读请求不注入 Idempotency-Key', async () => {
    adapterResult = { response: okEnvelope() }
    await request({ method: 'get', url: '/api/v1/ping' })
    expect(header(captured, 'Idempotency-Key')).toBe('')
  })
})

describe('http 错误与 RESULT_UNKNOWN', () => {
  it('202 RESULT_UNKNOWN → UnknownResultError', async () => {
    adapterResult = {
      response: {
        data: {
          request_id: 'r1',
          result_code: 'RESULT_UNKNOWN',
          result_message: 'x',
          http_status: 202,
          details: {},
        },
        status: 202,
        statusText: 'Accepted',
        headers: {},
        config: {},
        request: {},
      },
    }
    await expect(request({ method: 'post', url: '/api/v1/orders', data: {} })).rejects.toBeInstanceOf(
      UnknownResultError,
    )
  })

  it('非 2xx 错误信封 → ApiError', async () => {
    const cfg = { method: 'get', url: '/api/v1/ping' } as any
    const response = {
      data: {
        request_id: 'r1',
        result_code: 'VALIDATION_ERROR',
        result_message: 'bad',
        http_status: 400,
        details: {},
      },
      status: 400,
      statusText: 'Bad Request',
      headers: {},
      config: cfg,
      request: {},
    }
    const err = new AxiosError('Request failed with status code 400', 'ERR_BAD_REQUEST', cfg, null, response as any)
    adapterResult = { error: err }
    await expect(request(cfg)).rejects.toBeInstanceOf(ApiError)
  })

  it('写请求无响应（超时）→ UnknownResultError，不自动重试', async () => {
    const cfg = { method: 'post', url: '/api/v1/orders', data: {} } as any
    const err = new AxiosError('timeout of 10000ms exceeded', 'ECONNABORTED', cfg, null)
    adapterResult = { error: err }
    await expect(request(cfg)).rejects.toBeInstanceOf(UnknownResultError)
  })
})
