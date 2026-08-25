// __PREVIEW_SHOT__ 截图辅助脚本：三张 P1 原型页截图；不属于测试套件，用完保留在 scripts/ 亦可复用
import { chromium } from '@playwright/test'

const BASE = 'http://localhost:5199'
const OUT = 'E:/github/sports/.project-ai/previews'

const PAGES = [
  { url: '/ai/signals', file: `${OUT}/M-AI-001.png` },
  { url: '/growth', file: `${OUT}/M-GROWTH-001.png` },
  { url: '/prediction/free', file: `${OUT}/M-PREDICT-FREE-001.png` },
]

// 找到免费预测真实路径
const ROUTES = await fetch(`${BASE}/src/router/index.ts`).then((r) => r.text()).catch(() => '')
const freePath = ROUTES.match(/path:\s*'([^']*predict-free[^']*)'/)?.[1] ?? '/prediction/free'

const browser = await chromium.launch()
const ctx = await browser.newContext({ viewport: { width: 390, height: 844 }, deviceScaleFactor: 2 })
const page = await ctx.newPage()

await page.goto(`${BASE}/`, { waitUntil: 'domcontentloaded' })
await page.evaluate(() => {
  localStorage.setItem('session', JSON.stringify({ accessToken: 'preview-token', locale: 'zh-CN' }))
})

for (const p of PAGES) {
  const target = p.url === '/prediction/free' ? freePath : p.url
  await page.goto(`${BASE}${target}`, { waitUntil: 'networkidle' })
  await page.waitForTimeout(600)
  await page.screenshot({ path: p.file, fullPage: true })
  console.log(`SHOT OK: ${p.url} -> ${p.file}`)
}

await browser.close()
