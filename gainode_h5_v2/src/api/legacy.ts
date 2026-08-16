import { useUserStore } from '../stores/user'
import { showToast } from '../utils/toast'
import router from '../router'

let tokenExpiredShown = false

export function resetTokenExpiredFlag() {
  tokenExpiredShown = false
}

export interface ApiResponse<T = any> {
  code: number
  message: string
  data: T
}

// ... rest of constants and MD5 helpers stay the same

const BASE_URL = ''
const SIGN_KEY = 'projectApi'
const VERSION = '1.0'

function generateTraceId(): string {
  const chars = '0123456789abcdef'
  let buf = ''
  for (let i = 0; i < 32; i++) {
    if (i === 12) buf += '4'
    else if (i === 16) buf += chars[(Math.random() * 4) | 8]
    else buf += chars[Math.floor(Math.random() * 16)]
  }
  return buf
}

async function md5Hash(str: string): Promise<string> {
  const encoder = new TextEncoder()
  const data = encoder.encode(str)
  const hash = await crypto.subtle.digest('MD5', data).catch(() => null)
  if (hash) {
    return Array.from(new Uint8Array(hash))
      .map((b) => b.toString(16).padStart(2, '0'))
      .join('')
      .toUpperCase()
  }
  // Fallback: simple hash if SubtleCrypto doesn't support MD5
  return simpleMd5(str)
}

function simpleMd5(str: string): string {
  function cmn(q: number, a: number, b: number, x: number, s: number, t: number) { a = add32(add32(a, q), add32(x, t)); return add32((a << s) | (a >>> (32 - s)), b) }
  function ff(a: number, b: number, c: number, d: number, x: number, s: number, t: number) { return cmn((b & c) | (~b & d), a, b, x, s, t) }
  function gg(a: number, b: number, c: number, d: number, x: number, s: number, t: number) { return cmn((b & d) | (c & ~d), a, b, x, s, t) }
  function hh(a: number, b: number, c: number, d: number, x: number, s: number, t: number) { return cmn(b ^ c ^ d, a, b, x, s, t) }
  function ii(a: number, b: number, c: number, d: number, x: number, s: number, t: number) { return cmn(c ^ (b | ~d), a, b, x, s, t) }
  function add32(a: number, b: number) { return (a + b) & 0xffffffff }

  let n = str.length
  const state = [1732584193, -271733879, -1732584194, 271733878]
  const tail: number[] = Array(16).fill(0)
  let i: number

  for (i = 64; i <= n; i += 64) {
    const blk: number[] = []
    const chunk = str.substring(i - 64, i)
    for (let j = 0; j < 64; j += 4) blk[j >> 2] = chunk.charCodeAt(j) + (chunk.charCodeAt(j + 1) << 8) + (chunk.charCodeAt(j + 2) << 16) + (chunk.charCodeAt(j + 3) << 24)
    md5cycle(state, blk)
  }
  str = str.substring(i - 64)
  for (i = 0; i < str.length; i++) tail[i >> 2] = (tail[i >> 2]! | str.charCodeAt(i) << ((i % 4) << 3))
  tail[i >> 2] = (tail[i >> 2]! | 0x80 << ((i % 4) << 3))
  if (i > 55) { md5cycle(state, tail); for (let j = 0; j < 16; j++) tail[j] = 0 }
  tail[14] = n * 8
  md5cycle(state, tail)

  const hex = '0123456789abcdef'
  let result = ''
  for (i = 0; i < 4; i++) {
    for (let j = 0; j < 4; j++) {
      result += hex.charAt((state[i]! >> (j * 8 + 4)) & 0xf) + hex.charAt((state[i]! >> (j * 8)) & 0xf)
    }
  }
  return result.toUpperCase()

  function md5cycle(x: number[], k: number[]) {
    let a = x[0]!, b = x[1]!, c = x[2]!, d = x[3]!
    const K = k
    a = ff(a, b, c, d, K[0]!, 7, -680876936); d = ff(d, a, b, c, K[1]!, 12, -389564586); c = ff(c, d, a, b, K[2]!, 17, 606105819); b = ff(b, c, d, a, K[3]!, 22, -1044525330)
    a = ff(a, b, c, d, K[4]!, 7, -176418897); d = ff(d, a, b, c, K[5]!, 12, 1200080426); c = ff(c, d, a, b, K[6]!, 17, -1473231341); b = ff(b, c, d, a, K[7]!, 22, -45705983)
    a = ff(a, b, c, d, K[8]!, 7, 1770035416); d = ff(d, a, b, c, K[9]!, 12, -1958414417); c = ff(c, d, a, b, K[10]!, 17, -42063); b = ff(b, c, d, a, K[11]!, 22, -1990404162)
    a = ff(a, b, c, d, K[12]!, 7, 1804603682); d = ff(d, a, b, c, K[13]!, 12, -40341101); c = ff(c, d, a, b, K[14]!, 17, -1502002290); b = ff(b, c, d, a, K[15]!, 22, 1236535329)
    a = gg(a, b, c, d, K[1]!, 5, -165796510); d = gg(d, a, b, c, K[6]!, 9, -1069501632); c = gg(c, d, a, b, K[11]!, 14, 643717713); b = gg(b, c, d, a, K[0]!, 20, -373897302)
    a = gg(a, b, c, d, K[5]!, 5, -701558691); d = gg(d, a, b, c, K[10]!, 9, 38016083); c = gg(c, d, a, b, K[15]!, 14, -660478335); b = gg(b, c, d, a, K[4]!, 20, -405537848)
    a = gg(a, b, c, d, K[9]!, 5, 568446438); d = gg(d, a, b, c, K[14]!, 9, -1019803690); c = gg(c, d, a, b, K[3]!, 14, -187363961); b = gg(b, c, d, a, K[8]!, 20, 1163531501)
    a = gg(a, b, c, d, K[13]!, 5, -1444681467); d = gg(d, a, b, c, K[2]!, 9, -51403784); c = gg(c, d, a, b, K[7]!, 14, 1735328473); b = gg(b, c, d, a, K[12]!, 20, -1926607734)
    a = hh(a, b, c, d, K[5]!, 4, -378558); d = hh(d, a, b, c, K[8]!, 11, -2022574463); c = hh(c, d, a, b, K[11]!, 16, 1839030562); b = hh(b, c, d, a, K[14]!, 23, -35309556)
    a = hh(a, b, c, d, K[1]!, 4, -1530992060); d = hh(d, a, b, c, K[4]!, 11, 1272893353); c = hh(c, d, a, b, K[7]!, 16, -155497632); b = hh(b, c, d, a, K[10]!, 23, -1094730640)
    a = hh(a, b, c, d, K[13]!, 4, 681279174); d = hh(d, a, b, c, K[0]!, 11, -358537222); c = hh(c, d, a, b, K[3]!, 16, -722521979); b = hh(b, c, d, a, K[6]!, 23, 76029189)
    a = hh(a, b, c, d, K[9]!, 4, -640364487); d = hh(d, a, b, c, K[12]!, 11, -421815835); c = hh(c, d, a, b, K[15]!, 16, 530742520); b = hh(b, c, d, a, K[2]!, 23, -995338651)
    a = ii(a, b, c, d, K[0]!, 6, -198630844); d = ii(d, a, b, c, K[7]!, 10, 1126891415); c = ii(c, d, a, b, K[14]!, 15, -1416354905); b = ii(b, c, d, a, K[5]!, 21, -57434055)
    a = ii(a, b, c, d, K[12]!, 6, 1700485571); d = ii(d, a, b, c, K[3]!, 10, -1894986606); c = ii(c, d, a, b, K[10]!, 15, -1051523); b = ii(b, c, d, a, K[1]!, 21, -2054922799)
    a = ii(a, b, c, d, K[8]!, 6, 1873313359); d = ii(d, a, b, c, K[15]!, 10, -30611744); c = ii(c, d, a, b, K[6]!, 15, -1560198380); b = ii(b, c, d, a, K[13]!, 21, 1309151649)
    a = ii(a, b, c, d, K[4]!, 6, -145523070); d = ii(d, a, b, c, K[11]!, 10, -1120210379); c = ii(c, d, a, b, K[2]!, 15, 718787259); b = ii(b, c, d, a, K[9]!, 21, -343485551)
    x[0] = add32(a, x[0]!); x[1] = add32(b, x[1]!); x[2] = add32(c, x[2]!); x[3] = add32(d, x[3]!)
  }
}

function generateSign(language: string, timestamp: string, token: string, traceId: string): string {
  const raw = `Language=${language}&Timestamp=${timestamp}&Token=${token}&TraceId=${traceId}&Version=${VERSION}&Key=${SIGN_KEY}`
  return simpleMd5(raw)
}

function buildHeaders(): Record<string, string> {
  const timestamp = Math.floor(Date.now() / 1000).toString()
  const traceId = generateTraceId()
  const language = localStorage.getItem('language') || 'zh_CN'
  const token = localStorage.getItem('auth_token') || ''
  const sign = generateSign(language, timestamp, token, traceId)

  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    Timestamp: timestamp,
    Version: VERSION,
    Language: language,
    TraceId: traceId,
    Sign: sign,
  }
  if (token) headers['Token'] = token
  return headers
}

async function handleResponse<T>(response: Response): Promise<ApiResponse<T>> {
  let body: any
  try {
    body = await response.json()
  } catch {
    return { code: -1, message: '服务器响应异常', data: null as any }
  }

  const apiResp: ApiResponse<T> = {
    code: body.code ?? -1,
    message: body.msg || body.message || '',
    data: body.data,
  }

  // code === 0 成功，直接返回
  if (apiResp.code === 0) return apiResp

  // code === 4001 Token 过期，跳转登录（仅提示一次）
  if (apiResp.code === 4001) {
    if (!tokenExpiredShown) {
      tokenExpiredShown = true
      showToast('Token 已过期，请重新登录')
    }
    useUserStore().clear()
    router.replace('/login')
    return apiResp
  }

  // 其他错误码，弹出 msg
  if (apiResp.message) {
    showToast(apiResp.message)
  }

  return apiResp
}

export async function apiGet<T = any>(path: string, params?: Record<string, string>): Promise<ApiResponse<T>> {
  let url = `${BASE_URL}${path}`
  if (params) {
    const qs = new URLSearchParams(params).toString()
    if (qs) url += `?${qs}`
  }
  try {
    const res = await fetch(url, { headers: buildHeaders() })
    return handleResponse<T>(res)
  } catch {
    return { code: -1, message: '网络请求超时或失败', data: null as any }
  }
}

export async function apiPost<T = any>(path: string, data?: Record<string, any>): Promise<ApiResponse<T>> {
  try {
    const res = await fetch(`${BASE_URL}${path}`, {
      method: 'POST',
      headers: buildHeaders(),
      body: JSON.stringify(data || {}),
    })
    return handleResponse<T>(res)
  } catch {
    return { code: -1, message: '网络请求超时或失败', data: null as any }
  }
}

export async function apiPut<T = any>(path: string, data?: Record<string, any>): Promise<ApiResponse<T>> {
  try {
    const res = await fetch(`${BASE_URL}${path}`, {
      method: 'PUT',
      headers: buildHeaders(),
      body: JSON.stringify(data || {}),
    })
    return handleResponse<T>(res)
  } catch {
    return { code: -1, message: '网络请求超时或失败', data: null as any }
  }
}

export async function apiDelete<T = any>(path: string): Promise<ApiResponse<T>> {
  try {
    const res = await fetch(`${BASE_URL}${path}`, {
      method: 'DELETE',
      headers: buildHeaders(),
    })
    return handleResponse<T>(res)
  } catch {
    return { code: -1, message: '网络请求超时或失败', data: null as any }
  }
}
