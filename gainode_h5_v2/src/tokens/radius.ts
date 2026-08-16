/**
 * 圆角与边框 Token —— 来源：08 §6。
 */
export const radius = {
  sm: '8px',
  md: '12px',
  lg: '16px',
  xl: '20px',
} as const

export const border = {
  default: '1px solid #E2E8F0',
} as const

export type RadiusToken = typeof radius
