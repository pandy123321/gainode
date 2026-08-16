import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'

const pushMock = vi.hoisted(() => vi.fn())
vi.mock('vue-router', () => ({
  useRouter: () => ({ push: pushMock }),
  useRoute: () => ({ params: { id: 'le0' } }),
}))

const balanceMock = vi.hoisted(() => vi.fn())
const ledgerMock = vi.hoisted(() => vi.fn())
vi.mock('../../src/api/asset', () => ({
  assetApi: { balance: balanceMock, ledgerEntries: ledgerMock },
}))

const positionMock = vi.hoisted(() => vi.fn())
vi.mock('../../src/api/power', () => ({
  powerApi: { position: positionMock },
}))

import AssetRoot from '../../src/views/asset/m-asset-001/index.vue'
import AssetLedger from '../../src/views/asset/m-asset-002/index.vue'
import AssetDetail from '../../src/views/asset/m-asset-003/index.vue'
import PowerRoot from '../../src/views/power/m-power-001/index.vue'

const RouterLinkStub = {
  props: ['to'],
  template: '<a :href="typeof to === \'string\' ? to : to.path"><slot /></a>',
}

beforeEach(() => {
  setActivePinia(createPinia())
  pushMock.mockReset()
  balanceMock.mockReset()
  ledgerMock.mockReset()
  positionMock.mockReset()

  balanceMock.mockResolvedValue({
    request_id: 'r1',
    data: {
      account_id: 'a1',
      balance_apt_i: '100.5',
      frozen_apt_i: '10',
      aggregate_dispute_hold: '2',
      effective_available: '98.5',
    },
  })
  ledgerMock.mockResolvedValue({ request_id: 'r1', data: [] })
  positionMock.mockResolvedValue({
    request_id: 'r1',
    data: { user_id: 'u1', available: '30', limit: '100' },
  })
})

describe('M-ASSET-001 APT 资产', () => {
  it('渲染余额 + 可用/冻结/待确认分离', async () => {
    const w = mount(AssetRoot, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="asset-balance"]').exists()).toBe(true)
    expect(w.find('[data-testid="asset-breakdown"]').text()).toContain('98.5')
    expect(w.find('[data-testid="asset-breakdown"]').text()).toContain('10')
    expect(w.text()).toContain('APT 资产')
  })

  it('OTC 挂买/挂卖 fail-closed（disabled，不开放真实挂单）', async () => {
    const w = mount(AssetRoot, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    const buy = w.find('[data-testid="otc-buy"]')
    const sell = w.find('[data-testid="otc-sell"]')
    expect(buy.attributes('disabled')).toBeDefined()
    expect(sell.attributes('disabled')).toBeDefined()
  })
})

describe('M-ASSET-002 APT 流水列表', () => {
  it('渲染流水列表（方向/状态/来源）', async () => {
    ledgerMock.mockResolvedValue({
      request_id: 'r1',
      data: [
        { ledger_entry_id: 'le0', account_id: 'a1', asset: 'APT-I', quantity: '5', entry_direction: 1, entry_type: 'REWARD', state: 'posted', created_time: 1700000000 },
        { ledger_entry_id: 'le1', account_id: 'a1', asset: 'APT-I', quantity: '3', entry_direction: -1, entry_type: 'SPEND', state: 'reversed', created_time: 1700000001 },
      ],
    })
    const w = mount(AssetLedger, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.findAll('[data-testid="ledger-row"]').length).toBe(2)
    expect(w.text()).toContain('APT 流水')
  })

  it('无流水 → 空态', async () => {
    const w = mount(AssetLedger, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="ledger-empty"]').exists()).toBe(true)
  })
})

describe('M-ASSET-003 APT 流水详情', () => {
  it('渲染单笔详情（从列表取，无详情端点）', async () => {
    ledgerMock.mockResolvedValue({
      request_id: 'r1',
      data: [
        { ledger_entry_id: 'le0', account_id: 'a1', asset: 'APT-I', quantity: '5', entry_direction: 1, entry_type: 'REWARD', state: 'posted', reversal_of: 'le99', created_time: 1700000000 },
      ],
    })
    const w = mount(AssetDetail, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="ledger-detail"]').exists()).toBe(true)
    expect(w.text()).toContain('le0')
    expect(w.find('[data-testid="reversal"]').exists()).toBe(true)
  })

  it('深链 id 不在列表 → 未找到空态', async () => {
    ledgerMock.mockResolvedValue({ request_id: 'r1', data: [] })
    const w = mount(AssetDetail, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="ledger-not-found"]').exists()).toBe(true)
  })
})

describe('M-POWER-001 Power', () => {
  it('渲染 Battery + 状态拆分（权威只读）', async () => {
    const w = mount(PowerRoot, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="power-battery"]').exists()).toBe(true)
    expect(w.find('[data-testid="power-breakdown"]').text()).toContain('30')
    expect(w.text()).toContain('Power')
  })

  it('7 日趋势 / 冻结关联操作无冻结端点 → 空态不伪造', async () => {
    const w = mount(PowerRoot, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="power-trend"]').text()).toContain('暂不可用')
  })
})
