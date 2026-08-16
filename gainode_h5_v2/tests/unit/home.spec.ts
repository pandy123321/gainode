import { beforeEach, describe, expect, it, vi } from 'vitest'

const getMock = vi.hoisted(() => vi.fn())
vi.mock('../../src/api/http', () => ({
  get: getMock,
  post: vi.fn(),
}))

import { robotApi } from '../../src/api/robot'
import { predictionApi } from '../../src/api/prediction'
import { assetApi } from '../../src/api/asset'
import { setActivePinia, createPinia } from 'pinia'
import { useRobotStore } from '../../src/stores/robot'
import { usePredictionStore } from '../../src/stores/prediction'
import { useAssetStore } from '../../src/stores/asset'

beforeEach(() => {
  getMock.mockReset()
  getMock.mockResolvedValue({ request_id: 'r1', data: {} })
  setActivePinia(createPinia())
})

describe('Home 领域 API 绑定路径（契约缺口 S03-P02-HOME-*）', () => {
  it('robotApi.summary → GET /api/v1/ai/users/me/summary', async () => {
    await robotApi.summary()
    expect(getMock).toHaveBeenCalledWith('/api/v1/ai/users/me/summary')
  })

  it('predictionApi.markets → GET /api/v1/markets', async () => {
    await predictionApi.markets()
    expect(getMock).toHaveBeenCalledWith('/api/v1/markets')
  })

  it('assetApi.balance → GET /api/v1/me/asset', async () => {
    await assetApi.balance()
    expect(getMock).toHaveBeenCalledWith('/api/v1/me/asset')
  })
})

describe('useRobotStore', () => {
  it('fetch 成功写入 summary', async () => {
    getMock.mockResolvedValue({
      request_id: 'r1',
      data: { robot_id: 'rb1', level: 3, status: 'active' },
    })
    const store = useRobotStore()
    await store.fetch()
    expect(store.hasRobot).toBe(true)
    expect(store.summary?.level).toBe(3)
  })

  it('fetch 失败不抛，写入 error', async () => {
    getMock.mockRejectedValue(new Error('boom'))
    const store = useRobotStore()
    await store.fetch()
    expect(store.error).toBe('boom')
    expect(store.hasRobot).toBe(false)
  })
})

describe('usePredictionStore', () => {
  it('featuredMarkets 仅返回 open/closing', async () => {
    getMock.mockResolvedValue({
      request_id: 'r1',
      data: [
        { market_id: 'm1', event_id: 'e1', template_id: 'FOOTBALL_PREMATCH_1X2', market_status: 'open' },
        { market_id: 'm2', event_id: 'e2', template_id: 'FOOTBALL_PREMATCH_1X2', market_status: 'settled' },
        { market_id: 'm3', event_id: 'e3', template_id: 'FOOTBALL_PREMATCH_1X2', market_status: 'closing' },
      ],
    })
    const store = usePredictionStore()
    await store.fetchMarkets()
    expect(store.featuredMarkets.map((m) => m.market_id)).toEqual(['m1', 'm3'])
  })
})

describe('useAssetStore', () => {
  it('fetch 成功写入 balance', async () => {
    getMock.mockResolvedValue({
      request_id: 'r1',
      data: { account_id: 'a1', balance_apt_i: '10.5', effective_available: '10.5' },
    })
    const store = useAssetStore()
    await store.fetch()
    expect(store.hasBalance).toBe(true)
    expect(store.balance?.effective_available).toBe('10.5')
  })

  it('fetch 失败不抛，写入 error（单卡失败不拖垮整页）', async () => {
    getMock.mockRejectedValue(new Error('net down'))
    const store = useAssetStore()
    await store.fetch()
    expect(store.error).toBe('net down')
    expect(store.hasBalance).toBe(false)
  })
})
