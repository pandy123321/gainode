import { beforeEach, describe, expect, it, vi } from 'vitest'

const getMock = vi.hoisted(() => vi.fn())
const postMock = vi.hoisted(() => vi.fn())
vi.mock('../../src/api/http', () => ({
  get: getMock,
  post: postMock,
}))

import { kycApi, eligibilityApi } from '../../src/api/kyc'
import { kycStatusLabel, kycPrimaryActionKey } from '../../src/views/kyc/kycStatus'

beforeEach(() => {
  getMock.mockReset()
  postMock.mockReset()
  getMock.mockResolvedValue({ request_id: 'r1', data: {} })
  postMock.mockResolvedValue({ request_id: 'r1', data: {} })
})

describe('kycApi/eligibilityApi 绑定 OpenAPI 路径', () => {
  it('kycMe → GET /api/v1/me/kyc', async () => {
    await kycApi.kycMe()
    expect(getMock).toHaveBeenCalledWith('/api/v1/me/kyc')
  })

  it('kycSubmit → POST /api/v1/me/kyc/submit 透传 attachment_refs/consent_version', async () => {
    await kycApi.kycSubmit({
      kyc_level: 'standard',
      attachment_refs: ['a1', 'a2'],
      consent_version: '2026-08-01',
    })
    expect(postMock).toHaveBeenCalledWith('/api/v1/me/kyc/submit', {
      kyc_level: 'standard',
      attachment_refs: ['a1', 'a2'],
      consent_version: '2026-08-01',
    })
  })

  it('eligibilityMe → GET /api/v1/me/eligibility', async () => {
    await eligibilityApi.me()
    expect(getMock).toHaveBeenCalledWith('/api/v1/me/eligibility')
  })
})

describe('kycStatusLabel 展示映射（不新增领域状态）', () => {
  it('not_started → 未开始', () => {
    expect(kycStatusLabel('not_started')).toBe('未开始')
  })
  it('needs_info → 需补件', () => {
    expect(kycStatusLabel('needs_info')).toBe('需补件')
  })
  it('null → 未开始（兜底）', () => {
    expect(kycStatusLabel(null)).toBe('未开始')
  })
})

describe('kycPrimaryActionKey 主 CTA', () => {
  it('not_started → 开始验证', () => {
    expect(kycPrimaryActionKey('not_started')).toBe('page.m_kyc_001.primary_action')
  })
  it('needs_info → 补充资料', () => {
    expect(kycPrimaryActionKey('needs_info')).toBe('page.m_kyc_001.action_supplement')
  })
  it('approved → 查看结果', () => {
    expect(kycPrimaryActionKey('approved')).toBe('page.m_kyc_001.action_view')
  })
})
