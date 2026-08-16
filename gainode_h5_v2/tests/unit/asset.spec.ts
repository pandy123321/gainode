import { beforeEach, describe, expect, it, vi } from 'vitest'

const getMock = vi.hoisted(() => vi.fn())
vi.mock('../../src/api/http', () => ({
  get: getMock,
  post: vi.fn(),
}))

import { assetApi } from '../../src/api/asset'
import { powerApi } from '../../src/api/power'
import { setActivePinia, createPinia } from 'pinia'
import { useAssetStore } from '../../src/stores/asset'
import { usePowerStore } from '../../src/stores/power'

beforeEach(() => {
  getMock.mockReset()
  getMock.mockResolvedValue({ request_id: 'r1', data: {} })
  setActivePinia(createPinia())
})

describe('Asset/Power 领域 API 绑定路径', () => {
  it('assetApi.balance → GET /api/v1/me/asset', async () => {
    await assetApi.balance()
    expect(getMock).toHaveBeenCalledWith('/api/v1/me/asset')
  })

  it('assetApi.ledgerEntries → GET /api/v1/me/ledger-entries', async () => {
    await assetApi.ledgerEntries()
    expect(getMock).toHaveBeenCalledWith('/api/v1/me/ledger-entries')
  })

  it('powerApi.position → GET /api/v1/me/power', async () => {
    await powerApi.position()
    expect(getMock).toHaveBeenCalledWith('/api/v1/me/power')
  })
})

describe('useAssetStore', () => {
  it('fetch 写入 balance（可用/冻结/待确认分离）', async () => {
    getMock.mockResolvedValue({
      request_id: 'r1',
      data: {
        account_id: 'a1',
        balance_apt_i: '100.5',
        frozen_apt_i: '10',
        aggregate_dispute_hold: '2',
        effective_available: '98.5',
      },
    })
    const store = useAssetStore()
    await store.fetch()
    expect(store.balance?.effective_available).toBe('98.5')
    expect(store.balance?.frozen_apt_i).toBe('10')
    expect(store.balance?.aggregate_dispute_hold).toBe('2')
  })

  it('fetchLedger 写入流水 + recentLedger 截断 + entryById 查找', async () => {
    const entries = Array.from({ length: 7 }, (_, i) => ({
      ledger_entry_id: `le${i}`,
      account_id: 'a1',
      asset: 'APT-I' as const,
      quantity: '1',
      entry_direction: 1 as const,
      entry_type: 'REWARD',
      state: 'posted' as const,
    }))
    getMock.mockResolvedValue({ request_id: 'r1', data: entries })
    const store = useAssetStore()
    await store.fetchLedger()
    expect(store.ledger.length).toBe(7)
    expect(store.recentLedger.length).toBe(5)
    expect(store.entryById('le3')?.ledger_entry_id).toBe('le3')
    expect(store.entryById('missing')).toBeNull()
  })
})

describe('usePowerStore', () => {
  it('fetch 写入持仓 + ratio 计算（仅视觉，不参与业务）', async () => {
    getMock.mockResolvedValue({
      request_id: 'r1',
      data: {
        user_id: 'u1',
        available: '30',
        limit: '100',
        frozen: '5',
        power_cap_source_robot_level: 12,
      },
    })
    const store = usePowerStore()
    await store.fetch()
    expect(store.position?.available).toBe('30')
    expect(store.ratio).toBe(0.3)
  })

  it('limit 缺失/非正数 → ratio 为 null', async () => {
    getMock.mockResolvedValue({
      request_id: 'r1',
      data: { user_id: 'u1', available: '30' },
    })
    const store = usePowerStore()
    await store.fetch()
    expect(store.ratio).toBeNull()
  })
})
