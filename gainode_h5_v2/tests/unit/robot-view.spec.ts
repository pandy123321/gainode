import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'

const pushMock = vi.hoisted(() => vi.fn())
vi.mock('vue-router', () => ({
  useRouter: () => ({ push: pushMock }),
  useRoute: () => ({ params: { id: 'uo1' } }),
}))

const summaryMock = vi.hoisted(() => vi.fn())
const detailMock = vi.hoisted(() => vi.fn())
const rewardsMock = vi.hoisted(() => vi.fn())
const upgradeOrderMock = vi.hoisted(() => vi.fn())
vi.mock('../../src/api/robot', () => ({
  robotApi: {
    summary: summaryMock,
    detail: detailMock,
    rewards: rewardsMock,
    upgradeOrder: upgradeOrderMock,
  },
}))

import RobotRoot from '../../src/views/robot/m-robot-001/index.vue'
import RobotRewards from '../../src/views/robot/m-robot-006/index.vue'

const RouterLinkStub = {
  props: ['to'],
  template: '<a :href="typeof to === \'string\' ? to : to.path"><slot /></a>',
}

beforeEach(() => {
  setActivePinia(createPinia())
  pushMock.mockReset()
  summaryMock.mockReset()
  detailMock.mockReset()
  rewardsMock.mockReset()
  upgradeOrderMock.mockReset()

  summaryMock.mockResolvedValue({
    request_id: 'r1',
    data: { robot_id: 'rb1', level: 3, status: 'active', standard_capacity: '100' },
  })
  detailMock.mockResolvedValue({
    request_id: 'r1',
    data: { robot_id: 'rb1', user_id: 'u1', level: 3, status: 'active', capabilities: ['SPORT'], allowed_actions: [] },
  })
  rewardsMock.mockResolvedValue({ request_id: 'r1', data: [] })
  upgradeOrderMock.mockResolvedValue({ request_id: 'r1', data: {} })
})

describe('M-ROBOT-001 控制中心', () => {
  it('正常态渲染状态 Hero + 能力 + 导航入口', async () => {
    const w = mount(RobotRoot, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="robot-hero"]').text()).toContain('运行中')
    expect(w.find('[data-testid="robot-hero"]').text()).toContain('Lv.3')
    expect(w.find('[data-testid="entry-start"]').exists()).toBe(true)
    expect(w.find('[data-testid="entry-rewards"]').exists()).toBe(true)
  })

  it('无 Robot → 空态', async () => {
    summaryMock.mockResolvedValue({ request_id: 'r1', data: {} })
    const w = mount(RobotRoot, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="robot-empty"]').exists()).toBe(true)
  })

  it('source_status=UNAVAILABLE → 整页 Restricted', async () => {
    summaryMock.mockResolvedValue({ request_id: 'r1', data: { robot_id: 'rb1', level: 3, status: 'active' } })
    detailMock.mockResolvedValue({
      request_id: 'r1',
      data: { robot_id: 'rb1', user_id: 'u1', level: 3, status: 'active', source_status: 'UNAVAILABLE' },
    })
    const w = mount(RobotRoot, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="fs-restricted"]').exists()).toBe(true)
  })

  it('导航入口点击跳转', async () => {
    const w = mount(RobotRoot, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    await w.find('[data-testid="entry-upgrade"]').trigger('click')
    expect(pushMock).toHaveBeenCalledWith('/robot/upgrade')
  })
})

describe('M-ROBOT-006 收益与领取', () => {
  it('Claim 按钮 fail-closed（disabled）', async () => {
    rewardsMock.mockResolvedValue({
      request_id: 'r1',
      data: [{ reward_id: 'w1', user_id: 'u1', robot_id: 'rb1', state: 'held', quantity_apt: '5' }],
    })
    const w = mount(RobotRewards, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    const btn = w.find('[data-testid="claim-restricted"] button')
    expect(btn.exists()).toBe(true)
    expect(btn.attributes('disabled')).toBeDefined()
  })
})
