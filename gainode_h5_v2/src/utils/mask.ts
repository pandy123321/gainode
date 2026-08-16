/**
 * 账号脱敏展示（安全：不完整展示邮箱/手机号）。
 * 邮箱保留首 2 + 尾 1；手机号保留前 3 + 后 4。
 */
export function maskAccount(account: string): string {
  if (!account) return ''
  if (account.includes('@')) {
    const at = account.indexOf('@')
    const local = account.slice(0, at)
    const domain = account.slice(at)
    const head = local.slice(0, 2)
    const tail = local.slice(-1)
    return `${head}***${tail}${domain}`
  }
  if (account.length > 7) {
    return `${account.slice(0, 3)}****${account.slice(-4)}`
  }
  return `${account.slice(0, 1)}***`
}
