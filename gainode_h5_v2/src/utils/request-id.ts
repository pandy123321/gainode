/**
 * X-Request-Id 生成（≤64，UUID v4 无连字符）。
 */
export function generateRequestId(): string {
  if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
    return crypto.randomUUID().replace(/-/g, '')
  }
  const bytes = new Uint8Array(16)
  for (let i = 0; i < 16; i++) bytes[i] = Math.floor(Math.random() * 256)
  bytes[6] = (bytes[6]! & 0x0f) | 0x40
  bytes[8] = (bytes[8]! & 0x3f) | 0x80
  const hex = Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('')
  return hex
}

/** Idempotency-Key 生成（写操作，≤64，UUID v4 无连字符）。 */
export function generateIdempotencyKey(): string {
  return generateRequestId()
}

/** Unix 秒（X-Timestamp）。 */
export function nowUnixSeconds(): string {
  return Math.floor(Date.now() / 1000).toString()
}
