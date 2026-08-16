import { describe, expect, it } from 'vitest'
import zhCN from '../../src/i18n/locales/zh-CN.json'
import enUS from '../../src/i18n/locales/en-US.json'
import jaJP from '../../src/i18n/locales/ja-JP.json'
import koKR from '../../src/i18n/locales/ko-KR.json'
import thTH from '../../src/i18n/locales/th-TH.json'
import deDE from '../../src/i18n/locales/de-DE.json'
import frFR from '../../src/i18n/locales/fr-FR.json'

const locales: Record<string, Record<string, unknown>> = {
  'zh-CN': zhCN,
  'en-US': enUS,
  'ja-JP': jaJP,
  'ko-KR': koKR,
  'th-TH': thTH,
  'de-DE': deDE,
  'fr-FR': frFR,
}

function collectKeys(obj: unknown, prefix = ''): string[] {
  if (obj && typeof obj === 'object' && !Array.isArray(obj)) {
    return Object.entries(obj as Record<string, unknown>).flatMap(([k, v]) =>
      collectKeys(v, prefix ? `${prefix}.${k}` : k),
    )
  }
  return [prefix]
}

describe('i18n 7 语言 key parity', () => {
  it('7 语言 key 集完全一致', () => {
    const sets = Object.values(locales).map((l) => collectKeys(l).sort())
    const [base, ...rest] = sets
    expect(base.length).toBeGreaterThan(0)
    for (const s of rest) expect(s).toEqual(base)
  })

  it('包含 common.* 基础 key', () => {
    const keys = collectKeys(zhCN)
    expect(keys).toEqual(
      expect.arrayContaining([
        'common.loading',
        'common.empty',
        'common.error',
        'common.retry',
        'common.restricted',
        'common.unknownResult',
        'common.confirm',
      ]),
    )
  })

  it('敏感文案 otc.risk_disclosure.body 存在', () => {
    for (const l of Object.values(locales)) {
      expect(collectKeys(l)).toContain('otc.risk_disclosure.body')
    }
  })
})
