/**
 * 多语言入口（vue-i18n 7 语言 JSON）。
 * H5-04 修复：移除 legacy TS 语言包双轨（zh-CN.ts/en-US.ts 已随 V1 死代码集群删除）。
 * t() 现在直接走 vue-i18n（7 个 JSON locale，key parity 由 tests/unit/i18n.spec.ts 保证）。
 */
import { createI18n } from 'vue-i18n'
import zhCN from './locales/zh-CN.json'
import enUS from './locales/en-US.json'
import jaJP from './locales/ja-JP.json'
import koKR from './locales/ko-KR.json'
import thTH from './locales/th-TH.json'
import deDE from './locales/de-DE.json'
import frFR from './locales/fr-FR.json'

export const SUPPORTED_LOCALES = [
  'zh-CN',
  'en-US',
  'ja-JP',
  'ko-KR',
  'th-TH',
  'de-DE',
  'fr-FR',
] as const
export type SupportedLocale = (typeof SUPPORTED_LOCALES)[number]

export const i18n = createI18n({
  legacy: true,
  locale: 'zh-CN',
  fallbackLocale: 'en-US',
  messages: {
    'zh-CN': zhCN,
    'en-US': enUS,
    'ja-JP': jaJP,
    'ko-KR': koKR,
    'th-TH': thTH,
    'de-DE': deDE,
    'fr-FR': frFR,
  },
})

const STORAGE_KEY = 'app_lang'

function resolveLocale(lang: string): SupportedLocale {
  return (SUPPORTED_LOCALES as readonly string[]).includes(lang)
    ? (lang as SupportedLocale)
    : 'zh-CN'
}

/** 统一翻译入口：vue-i18n + {param} 插值 */
export function t(key: string, params?: Record<string, string>): string {
  let text = String(i18n.global.t(key))
  if (params) {
    for (const [k, v] of Object.entries(params)) {
      text = text.replace(`{${k}}`, v)
    }
  }
  return text
}

export function setLanguage(lang: string): void {
  const resolved = resolveLocale(lang)
  try {
    localStorage.setItem(STORAGE_KEY, resolved)
  } catch {
    /* ignore */
  }
  i18n.global.locale = resolved
}

export function getCurrentLanguage(): string {
  return i18n.global.locale
}

export function getSupportedLanguages(): { code: string; name: string; nativeName: string }[] {
  return [
    { code: 'zh-CN', name: 'Simplified Chinese', nativeName: '简体中文' },
    { code: 'en-US', name: 'English', nativeName: 'English' },
    { code: 'ja-JP', name: 'Japanese', nativeName: '日本語' },
    { code: 'ko-KR', name: 'Korean', nativeName: '한국어' },
    { code: 'th-TH', name: 'Thai', nativeName: 'ไทย' },
    { code: 'de-DE', name: 'German', nativeName: 'Deutsch' },
    { code: 'fr-FR', name: 'French', nativeName: 'Français' },
  ]
}

// 应用启动时从 localStorage 恢复语言
try {
  const stored = localStorage.getItem(STORAGE_KEY)
  if (stored) i18n.global.locale = resolveLocale(stored)
} catch {
  /* ignore */
}
