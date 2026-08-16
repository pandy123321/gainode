/**
 * 8pt 间距 Token —— 来源：08 §5。
 */
export const spacing = {
  1: '4px',
  2: '8px',
  3: '12px',
  4: '16px',
  5: '20px',
  6: '24px',
  8: '32px',
  10: '40px',
  12: '48px',
} as const

/** 页面左右边距 / 卡片内边距（Mobile） */
export const layout = {
  pageGutter: '16px',
  cardPadding: '16px',
  sectionGap: '24px',
  appBar: '56px',
  bottomNav: '64px',
} as const

export type SpacingToken = typeof spacing
