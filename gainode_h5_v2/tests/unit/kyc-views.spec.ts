import { beforeEach, describe, expect, it, vi } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

const replaceMock = vi.hoisted(() => vi.fn())
vi.mock('vue-router', () => ({
  useRouter: () => ({ replace: replaceMock, push: vi.fn() }),
}))

const kycMeMock = vi.hoisted(() => vi.fn())
const kycSubmitMock = vi.hoisted(() => vi.fn())
const eligibilityMeMock = vi.hoisted(() => vi.fn())
vi.mock('../../src/api/kyc', () => ({
  kycApi: { kycMe: kycMeMock, kycSubmit: kycSubmitMock },
  eligibilityApi: { me: eligibilityMeMock },
}))

const noticeListMock = vi.hoisted(() => vi.fn())
const noticeReadMock = vi.hoisted(() => vi.fn())
vi.mock('../../src/api/notice', () => ({
  noticeApi: { list: noticeListMock, read: noticeReadMock },
}))

import KYCFormView from '../../src/views/kyc/m-kyc-002/index.vue'
import KYCOverviewView from '../../src/views/kyc/m-kyc-001/index.vue'
import NoticeView from '../../src/views/notice/m-notice-001/index.vue'

beforeEach(() => {
  setActivePinia(createPinia())
  replaceMock.mockReset()
  kycMeMock.mockReset()
  kycSubmitMock.mockReset()
  eligibilityMeMock.mockReset()
  noticeListMock.mockReset()
  noticeReadMock.mockReset()
})

describe('M-KYC-002 资料提交', () => {
  it('未勾选同意 → 提交被拦截，提示先同意', async () => {
    const w = mount(KYCFormView)
    await w.find('.cta').trigger('click')
    expect(w.find('[data-testid="consent-error"]').exists()).toBe(true)
    expect(kycSubmitMock).not.toHaveBeenCalled()
  })

  it('勾选同意但无附件 → 提示至少上传一份', async () => {
    const w = mount(KYCFormView)
    await w.find('.consent-box').setValue(true)
    await w.find('.cta').trigger('click')
    expect(w.find('[data-testid="submit-error"]').exists()).toBe(true)
    expect(kycSubmitMock).not.toHaveBeenCalled()
  })
})

describe('M-KYC-001 概览', () => {
  it('展示 KYC 状态与功能能力清单', async () => {
    kycMeMock.mockResolvedValue({
      request_id: 'r1',
      data: { case_id: 'c1', user_id: 'u1', kyc_level: 'standard', status: 'approved' },
    })
    eligibilityMeMock.mockResolvedValue({
      request_id: 'r1',
      data: {
        user_id: 'u1',
        global_p: { feature_key: 'global_p', allowed: true },
        ai: { feature_key: 'ai', allowed: true },
        prediction: { feature_key: 'prediction', allowed: false },
      },
    })
    const w = mount(KYCOverviewView)
    await flushPromises()
    expect(w.text()).toContain('已通过')
    expect(w.text()).toContain('竞猜')
  })
})

describe('M-NOTICE-001 消息中心', () => {
  it('空列表 → 显示空态', async () => {
    noticeListMock.mockResolvedValue({ request_id: 'r1', data: [] })
    const w = mount(NoticeView)
    await flushPromises()
    expect(w.find('[data-testid="notice-empty"]').exists()).toBe(true)
  })

  it('有未读通知 → 点击标记已读', async () => {
    noticeListMock.mockResolvedValue({
      request_id: 'r1',
      data: [
        { notice_id: 'n1', user_id: 'u1', read_state: 'unread', notice_type: 'SYSTEM_ANNOUNCEMENT', title_key: 't1', body_key: 'b1' },
      ],
    })
    noticeReadMock.mockResolvedValue({ request_id: 'r1', data: {} })
    const w = mount(NoticeView)
    await flushPromises()
    expect(w.find('.notice-row').exists()).toBe(true)
    await w.find('.notice-row').trigger('click')
    await flushPromises()
    expect(noticeReadMock).toHaveBeenCalledWith('n1')
  })
})
