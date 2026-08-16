import { expect, test } from '@playwright/test'

test('首页渲染根壳', async ({ page }) => {
  await page.goto('/')
  await expect(page.locator('.root-shell')).toBeVisible()
})
