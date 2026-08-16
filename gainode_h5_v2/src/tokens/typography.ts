/**
 * 字体栈与 H5 字号 Token —— 来源：08 §4。
 */
export const fontFamily =
  'Inter, "PingFang SC", "HarmonyOS Sans SC", "Noto Sans SC", system-ui, sans-serif'

export const typography = {
  display: { fontSize: '28px', lineHeight: '36px', fontWeight: 700 },
  h1: { fontSize: '24px', lineHeight: '32px', fontWeight: 700 },
  h2: { fontSize: '20px', lineHeight: '28px', fontWeight: 650 },
  h3: { fontSize: '17px', lineHeight: '24px', fontWeight: 600 },
  body: { fontSize: '15px', lineHeight: '22px', fontWeight: 400 },
  bodyStrong: { fontSize: '15px', lineHeight: '22px', fontWeight: 600 },
  meta: { fontSize: '13px', lineHeight: '18px', fontWeight: 400 },
  caption: { fontSize: '12px', lineHeight: '16px', fontWeight: 400 },
  dataL: { fontSize: '28px', lineHeight: '34px', fontWeight: 700 },
  dataM: { fontSize: '20px', lineHeight: '28px', fontWeight: 650 },
} as const

export type TypographyToken = typeof typography
