import { t } from '../../i18n'
import type { KycStatus } from '../../api/kyc'

/** KycCase.status → 本地化标签（03 M-KYC 展示映射，不新增领域状态） */
export function kycStatusLabel(status: KycStatus | null | undefined): string {
  if (!status) return t('kyc.status.not_started')
  return t(`kyc.status.${status}`)
}

/** 状态 → 主 CTA 文案 key（M-KYC-001/003 共用） */
export function kycPrimaryActionKey(status: KycStatus | null | undefined): string {
  if (status === 'needs_info') return 'page.m_kyc_001.action_supplement'
  if (status === 'not_started') return 'page.m_kyc_001.primary_action'
  return 'page.m_kyc_001.action_view'
}
