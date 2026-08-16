/**
 * 品牌/中性/状态色 Token —— 唯一取色来源，业务页禁止硬编码品牌色。
 * 来源：08_VISUAL_DESIGN_SYSTEM_V2.4 §2。
 */
export const color = {
  brand: {
    navy950: '#071226',
    navy900: '#05285D',
    blue800: '#024EC2',
    blue600: '#057CF1',
    cyan500: '#06A9FE',
    cyan300: '#3ACFFD',
    gold500: '#F4D016',
    gold300: '#FFE27A',
  },
  gray: {
    gray950: '#0F172A',
    gray800: '#1E293B',
    gray700: '#334155',
    gray600: '#475569',
    gray500: '#64748B',
    gray400: '#94A3B8',
    gray300: '#CBD5E1',
    gray200: '#E2E8F0',
    gray100: '#F1F5F9',
    gray50: '#F8FAFC',
    white: '#FFFFFF',
  },
  status: {
    success600: '#059669',
    success100: '#D1FAE5',
    warning600: '#D97706',
    warning100: '#FEF3C7',
    danger600: '#DC2626',
    danger100: '#FEE2E2',
    info600: '#0284C7',
    info100: '#E0F2FE',
  },
} as const

export type ColorToken = typeof color
