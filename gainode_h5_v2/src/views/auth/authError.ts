/**
 * 认证页错误 → 本地化文案映射。禁止直接显示 raw enum / 服务端错误串。
 * 登录/注册不区分「账号是否存在」，统一回退通用提示（防枚举）。
 */
import { ApiError } from '../../api/types'
import { t } from '../../i18n'

export function authErrorMessage(e: unknown): string {
  if (e instanceof ApiError) {
    switch (e.result_code) {
      case 'AUTH_UNAUTHENTICATED':
        // 登录/注册统一「账号或密码错误」，不泄露账号是否存在
        return t('auth.invalid_credentials')
      case 'AUTH_FORBIDDEN':
      case 'POLICY_DENIED':
        return t('auth.account_locked')
      case 'FEATURE_CLOSED':
      case 'DEPENDENCY_UNAVAILABLE':
        return t('common.restricted')
      case 'VALIDATION_ERROR':
        return typeof e.details?.message === 'string'
          ? (e.details.message as string)
          : e.message || t('common.error')
      default:
        return e.message || t('common.error')
    }
  }
  return t('common.error')
}
