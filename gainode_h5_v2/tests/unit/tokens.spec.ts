import { describe, expect, it } from 'vitest'
import {
  border,
  color,
  elevation,
  fontFamily,
  layout,
  radius,
  spacing,
  typography,
} from '../../src/tokens'

describe('design tokens', () => {
  it('color 品牌/中性/状态色均有定义', () => {
    expect(color.brand.navy950).toMatch(/^#/)
    expect(color.brand.gold500).toBe('#F4D016')
    expect(color.gray.white).toBe('#FFFFFF')
    expect(color.status.danger600).toBe('#DC2626')
  })

  it('spacing 遵循 8pt 体系', () => {
    expect(spacing[1]).toBe('4px')
    expect(spacing[2]).toBe('8px')
    expect(spacing[8]).toBe('32px')
    expect(layout.pageGutter).toBe('16px')
    expect(layout.bottomNav).toBe('64px')
  })

  it('typography/radius/elevation/border 定义完整', () => {
    expect(typography.h1.fontSize).toBe('24px')
    expect(typography.body.fontWeight).toBe(400)
    expect(radius.md).toBe('12px')
    expect(elevation.card).toContain('rgba')
    expect(border.default).toContain('1px')
    expect(fontFamily).toContain('sans-serif')
  })
})
