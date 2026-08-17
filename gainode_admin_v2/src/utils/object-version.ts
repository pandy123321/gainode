/**
 * object_version 乐观锁冲突处理（05 §1 CONCURRENCY = If-Match / object_version）。
 * 后端返回 OBJECT_VERSION_CONFLICT(409) 时，前端不得静默重试覆盖他人改动，
 * 必须提示「数据已被他人修改」，引导用户刷新后重试。
 */
import { ElMessageBox } from 'element-plus'
import { ApiError } from '@/api/types'

/** 判断错误是否为乐观锁冲突 */
export function isObjectVersionConflict(err: unknown): err is ApiError {
  return (
    err instanceof ApiError &&
    (err as ApiError).isObjectVersionConflict
  )
}

export interface ConflictPromptOptions {
  /** 冲突提示标题 */
  title?: string
  /** 冲突提示正文 */
  message?: string
  /** 确认后回调（通常重新拉取详情刷新页面） */
  onRefresh?: () => void
}

/**
 * 弹出乐观锁冲突提示。用户确认后触发 onRefresh（刷新最新数据），
 * 不提供「强制覆盖」选项。
 */
export async function showObjectVersionConflict(
  options: ConflictPromptOptions = {},
): Promise<void> {
  const title = options.title ?? '数据已更新'
  const message = options.message ?? '该数据已被其他操作员修改，请刷新后重试，避免覆盖他人改动。'
  try {
    await ElMessageBox.alert(message, title, {
      confirmButtonText: '刷新',
      type: 'warning',
    })
    options.onRefresh?.()
  } catch {
    // 用户取消弹窗，不执行刷新
  }
}

/** 便捷入口：错误是乐观锁冲突时弹提示，返回是否已处理 */
export async function handleObjectVersionConflict(
  err: unknown,
  options: ConflictPromptOptions = {},
): Promise<boolean> {
  if (!isObjectVersionConflict(err)) return false
  await showObjectVersionConflict(options)
  return true
}
