import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'

const replaceMock = vi.hoisted(() => vi.fn())
vi.mock('vue-router', () => ({
  useRouter: () => ({ push: vi.fn(), replace: replaceMock }),
  useRoute: () => ({ params: {}, query: {} }),
}))

const userMeMock = vi.hoisted(() => vi.fn())
const securityProfileMock = vi.hoisted(() => vi.fn())
const sessionsMock = vi.hoisted(() => vi.fn())
const kycMeMock = vi.hoisted(() => vi.fn())
const logoutMock = vi.hoisted(() => vi.fn())

vi.mock('../../src/api/user', () => ({ userApi: { me: userMeMock } }))
vi.mock('../../src/api/security', () => ({
  securityApi: {
    securityProfile: securityProfileMock,
    sessions: sessionsMock,
    loginAudit: vi.fn(),
  },
}))
vi.mock('../../src/api/kyc', () => ({ kycApi: { kycMe: kycMeMock } }))
vi.mock('../../src/api/auth', () => ({ authApi: { logout: logoutMock } }))
vi.mock('../../src/api/http', () => ({
  setAccessToken: vi.fn(),
  setRefreshToken: vi.fn(),
}))

import MeRoot from '../../src/views/me/m-me-001/index.vue'
import SecRoot from '../../src/views/security/m-sec-001/index.vue'
import SecSessions from '../../src/views/security/m-sec-002/index.vue'
import Settings from '../../src/views/settings/m-settings-001/index.vue'

const RouterLinkStub = {
  props: ['to'],
  template: '<a :href="typeof to === \'string\' ? to : to.path"><slot /></a>',
}

beforeEach(() => {
  setActivePinia(createPinia())
  replaceMock.mockReset()
  userMeMock.mockReset()
  securityProfileMock.mockReset()
  sessionsMock.mockReset()
  kycMeMock.mockReset()
  logoutMock.mockReset()

  userMeMock.mockResolvedValue({
    request_id: 'r1',
    data: { user_id: 'u1', status: 'active', display_name: 'Ada', locale: 'zh-CN', global_p_level: 'P2', ai_reward_eligibility: true, prediction_eligibility: false },
  })
  securityProfileMock.mockResolvedValue({
    request_id: 'r1',
    data: { user_id: 'u1', mfa_enrolled_methods: ['totp'], suspicious_flags: [] },
  })
  sessionsMock.mockResolvedValue({
    request_id: 'r1',
    data: {
      sessions: [
        { session_id: 's1', is_current: true, revocable: false, os: 'iOS', browser: 'Safari', location_region: 'SG' },
        { session_id: 's2', is_current: false, revocable: true, os: 'Windows', browser: 'Chrome', location_region: 'US' },
      ],
    },
  })
  kycMeMock.mockResolvedValue({ request_id: 'r1', data: { case_id: 'c1', user_id: 'u1', kyc_level: 'L1', status: 'approved' } })
  logoutMock.mockResolvedValue({ request_id: 'r1', data: {} })
})

describe('M-ME-001 我的', () => {
  it('渲染用户摘要 + 状态（KYC/MFA）', async () => {
    const w = mount(MeRoot, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="me-profile"]').text()).toContain('Ada')
    expect(w.find('[data-testid="me-kyc-status"]').text()).toContain('已通过')
    expect(w.find('[data-testid="me-mfa-status"]').text()).toContain('已绑定')
  })

  it('入口分组渲染 + Support 未开放（coming soon）', async () => {
    const w = mount(MeRoot, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="me-entry-asset"]').exists()).toBe(true)
    expect(w.find('[data-testid="me-entry-security"]').exists()).toBe(true)
    expect(w.find('[data-testid="me-entry-settings"]').exists()).toBe(true)
    expect(w.find('[data-testid="me-entry-support"]').text()).toContain('敬请期待')
  })
})

describe('M-SEC-001 安全中心', () => {
  it('渲染 MFA 方式数 + 会话数', async () => {
    const w = mount(SecRoot, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="sec-mfa-count"]').text()).toBe('1')
    expect(w.find('[data-testid="sec-session-count"]').text()).toBe('2')
  })

  it('登录记录 fail-closed（restricted）', async () => {
    const w = mount(SecRoot, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="sec-audit"]').text()).toContain('来源未裁决')
  })
})

describe('M-SEC-002 MFA / 设备 / 会话', () => {
  it('渲染当前会话 + 其余可撤销会话（脱敏，无 IP/指纹）', async () => {
    const w = mount(SecSessions, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    const text = w.find('[data-testid="sessions-section"]').text()
    expect(text).toContain('iOS')
    expect(text).toContain('Windows')
    expect(text).not.toContain('192.')
  })

  it('MFA 绑定 + 会话撤销 fail-closed（disabled）', async () => {
    const w = mount(SecSessions, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="mfa-bind"]').attributes('disabled')).toBeDefined()
    expect(w.find('[data-testid="revoke-btn"]').attributes('disabled')).toBeDefined()
  })
})

describe('M-SETTINGS-001 设置', () => {
  it('渲染语言选项 + 偏好受限', async () => {
    const w = mount(Settings, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    expect(w.find('[data-testid="lang-zh-CN"]').exists()).toBe(true)
    expect(w.find('[data-testid="lang-en-US"]').exists()).toBe(true)
    expect(w.find('[data-testid="settings-preferences"]').text()).toContain('尚未开放')
  })

  it('退出登录 → 调用 logout + 本地清空 + 回登录页', async () => {
    const w = mount(Settings, { global: { stubs: { RouterLink: RouterLinkStub } } })
    await flushPromises()
    await w.find('[data-testid="logout-btn"]').trigger('click')
    await flushPromises()
    expect(logoutMock).toHaveBeenCalled()
    expect(replaceMock).toHaveBeenCalledWith('/auth/login')
  })
})
