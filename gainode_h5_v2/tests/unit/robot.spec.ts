import { beforeEach, describe, expect, it, vi } from 'vitest'

const getMock = vi.hoisted(() => vi.fn())
vi.mock('../../src/api/http', () => ({
  get: getMock,
  post: vi.fn(),
}))

import { robotApi } from '../../src/api/robot'
import { setActivePinia, createPinia } from 'pinia'
import { useRobotStore } from '../../src/stores/robot'

beforeEach(() => {
  getMock.mockReset()
  getMock.mockResolvedValue({ request_id: 'r1', data: {} })
  setActivePinia(createPinia())
})

describe('Robot 领域 API 绑定路径', () => {
  it('robotApi.detail → GET /api/v1/ai/robots/{robot_id}', async () => {
    await robotApi.detail('rb1')
    expect(getMock).toHaveBeenCalledWith('/api/v1/ai/robots/rb1')
  })

  it('robotApi.rewards → GET /api/v1/ai/users/me/rewards', async () => {
    await robotApi.rewards()
    expect(getMock).toHaveBeenCalledWith('/api/v1/ai/users/me/rewards')
  })

  it('robotApi.upgradeOrder → GET /api/v1/ai/users/me/upgrade-orders/{id}', async () => {
    await robotApi.upgradeOrder('uo1')
    expect(getMock).toHaveBeenCalledWith('/api/v1/ai/users/me/upgrade-orders/uo1')
  })
})

describe('useRobotStore', () => {
  it('fetchDetail 写入 detail + allowed_actions', async () => {
    getMock.mockResolvedValue({
      request_id: 'r1',
      data: {
        robot_id: 'rb1',
        user_id: 'u1',
        level: 3,
        status: 'active',
        allowed_actions: ['ROBOT_CLAIM'],
      },
    })
    const store = useRobotStore()
    await store.fetchDetail('rb1')
    expect(store.detail?.robot_id).toBe('rb1')
    expect(store.allowedActions).toContain('ROBOT_CLAIM')
    expect(store.sourceUnavailable).toBe(false)
  })

  it('sourceUnavailable = true 当 source_status=UNAVAILABLE', async () => {
    getMock.mockResolvedValue({
      request_id: 'r1',
      data: { robot_id: 'rb1', user_id: 'u1', level: 3, status: 'active', source_status: 'UNAVAILABLE' },
    })
    const store = useRobotStore()
    await store.fetchDetail('rb1')
    expect(store.sourceUnavailable).toBe(true)
  })

  it('claimableRewards 仅返回 held/pending_claim', async () => {
    getMock.mockResolvedValue({
      request_id: 'r1',
      data: [
        { reward_id: 'w1', user_id: 'u1', robot_id: 'rb1', state: 'held' },
        { reward_id: 'w2', user_id: 'u1', robot_id: 'rb1', state: 'pending_claim' },
        { reward_id: 'w3', user_id: 'u1', robot_id: 'rb1', state: 'claimed' },
      ],
    })
    const store = useRobotStore()
    await store.fetchRewards()
    expect(store.claimableRewards.map((r) => r.reward_id)).toEqual(['w1', 'w2'])
  })

  it('fetchUpgradeOrder 写入订单', async () => {
    getMock.mockResolvedValue({
      request_id: 'r1',
      data: {
        upgrade_order_id: 'uo1',
        robot_id: 'rb1',
        user_id: 'u1',
        from_level: 3,
        to_level: 4,
        status: 'completed',
      },
    })
    const store = useRobotStore()
    await store.fetchUpgradeOrder('uo1')
    expect(store.upgradeOrder?.status).toBe('completed')
  })
})
