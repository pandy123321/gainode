/**
 * 阴影 Token —— 来源：08 §6。
 */
export const elevation = {
  card: '0 4px 16px rgba(15, 23, 42, .06)',
  float: '0 12px 32px rgba(15, 23, 42, .12)',
} as const

export type ElevationToken = typeof elevation
