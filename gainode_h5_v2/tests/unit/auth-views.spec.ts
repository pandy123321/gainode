import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { authApi } from '../../src/api/auth'
import LoginView from '../../src/views/auth/m-auth-001/index.vue'
import RegisterView from '../../src/views/auth/m-auth-002/index.vue'

const replaceMock = vi.hoisted(() => vi.fn())
vi.mock('vue-router', () => ({
  useRouter: () => ({ replace: replaceMock }),
}))

const RouterLinkStub = { template: '<a><slot /></a>' }

function loginResp(mfa: boolean) {
  return {
    request_id: 'r1',
    data: { token_type: 'Bearer', access_token: 'at', expires_in: 3600, session_id: 's1', mfa_required: mfa },
  } as never
}

beforeEach(() => {
  setActivePinia(createPinia())
  replaceMock.mockReset()
  vi.restoreAllMocks()
})

describe('M-AUTH-001 登录页', () => {
  it('空账号提交 → 校验错误，不调用 login', async () => {
    const spy = vi.spyOn(authApi, 'login').mockResolvedValue(loginResp(false))
    const w = mount(LoginView, { global: { stubs: { 'router-link': RouterLinkStub } } })
    await w.find('[data-testid="login-submit"]').trigger('click')
    expect(w.find('[data-testid="auth-error"]').exists()).toBe(true)
    expect(spy).not.toHaveBeenCalled()
  })

  it('有效提交 → 调用 login 并导航首页', async () => {
    const spy = vi.spyOn(authApi, 'login').mockResolvedValue(loginResp(false))
    const w = mount(LoginView, { global: { stubs: { 'router-link': RouterLinkStub } } })
    await w.find('[data-testid="login-account"]').setValue('a@b.com')
    await w.find('[data-testid="login-password"]').setValue('password')
    await w.find('[data-testid="login-submit"]').trigger('click')
    await flushPromises()
    expect(spy).toHaveBeenCalledWith({ account: 'a@b.com', password: 'password' })
    expect(replaceMock).toHaveBeenCalledWith('/')
  })

  it('mfa_required → 导航到 /auth/mfa 保留 session 上下文', async () => {
    vi.spyOn(authApi, 'login').mockResolvedValue(loginResp(true))
    const w = mount(LoginView, { global: { stubs: { 'router-link': RouterLinkStub } } })
    await w.find('[data-testid="login-account"]').setValue('a@b.com')
    await w.find('[data-testid="login-password"]').setValue('password')
    await w.find('[data-testid="login-submit"]').trigger('click')
    await flushPromises()
    expect(replaceMock).toHaveBeenCalledWith('/auth/mfa')
  })
})

describe('M-AUTH-002 注册页', () => {
  it('条款不可默认勾选；未勾选提交 → 提示先同意条款', async () => {
    const spy = vi.spyOn(authApi, 'register').mockResolvedValue({ request_id: 'r1', data: {} } as never)
    const w = mount(RegisterView, { global: { stubs: { 'router-link': RouterLinkStub } } })
    // 未勾选（默认 false）
    const checkbox = w.find('[data-testid="register-consent"] .auth-checkbox')
    expect(checkbox.classes()).not.toContain('auth-checkbox--checked')
    await w.find('[data-testid="register-account"]').setValue('a@b.com')
    await w.find('[data-testid="register-password"]').setValue('password1')
    await w.find('[data-testid="register-confirm"]').setValue('password1')
    await w.find('[data-testid="register-submit"]').trigger('click')
    await flushPromises()
    expect(w.text()).toContain('请先勾选并同意条款')
    expect(spy).not.toHaveBeenCalled()
  })
})
