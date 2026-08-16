import { defineConfig, devices } from '@playwright/test'

export default defineConfig({
  testDir: './tests/e2e',
  timeout: 30_000,
  retries: 0,
  use: {
    baseURL: 'http://127.0.0.1:4173',
    trace: 'on-first-retry',
  },
  webServer: {
    command: 'npm run dev -- --host 127.0.0.1 --port 4173',
    url: 'http://127.0.0.1:4173',
    reuseExistingServer: true,
  },
  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
    // H5 三尺寸视觉基线（08 §2：375/390/430）
    { name: 'mobile-375', use: { ...devices['iPhone SE'] } },
    { name: 'mobile-390', use: { ...devices['iPhone 12'] } },
    { name: 'mobile-430', use: { ...devices['Pixel 7'] } },
  ],
})
