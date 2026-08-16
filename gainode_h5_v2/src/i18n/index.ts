/**
 * 多语言入口（vue-i18n 7 语言）+ legacy 兼容导出（旧 view 直到 S03-P02）。
 */
import { createI18n } from 'vue-i18n'
import zhCN from './locales/zh-CN.json'
import enUS from './locales/en-US.json'
import jaJP from './locales/ja-JP.json'
import koKR from './locales/ko-KR.json'
import thTH from './locales/th-TH.json'
import deDE from './locales/de-DE.json'
import frFR from './locales/fr-FR.json'
// legacy 旧 key（TS 模块，保留给旧 view，S03-P02 迁移后删除）
import legacyZhCN from './locales/zh-CN'
import legacyEnUS from './locales/en-US'

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

const legacyLocales: Record<string, Record<string, string>> = {
  'zh-CN': legacyZhCN as Record<string, string>,
  'en-US': legacyEnUS as Record<string, string>,
}

function resolveLocale(lang: string): SupportedLocale {
  return (SUPPORTED_LOCALES as readonly string[]).includes(lang)
    ? (lang as SupportedLocale)
    : 'zh-CN'
}

/** 兼容旧 view 的 t()：先查旧 key，再查 vue-i18n，最后回退 raw key */
export function t(key: string, params?: Record<string, string>): string {
  const current = i18n.global.locale
  let text =
    legacyLocales[current]?.[key] ??
    legacyLocales['zh-CN']?.[key] ??
    String(i18n.global.t(key))
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

export function getCurrentLocale(): Readonly<Record<string, string>> {
  return legacyLocales[getCurrentLanguage()] ?? legacyLocales['zh-CN'] ?? {}
}

// 应用启动时从 localStorage 恢复语言
try {
  const stored = localStorage.getItem(STORAGE_KEY)
  if (stored) i18n.global.locale = resolveLocale(stored)
} catch {
  /* ignore */
}
