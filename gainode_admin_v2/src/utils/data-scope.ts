/**
 * 字段 / 数据范围权限辅助（04 §12 / 05 §11）。
 * 权限公式 = canonical_role + data_scope + object_state + allowed_actions + risk_policy + SoD。
 *
 * 前端只读服务端返回的 allowed_actions 渲染按钮，不做本地权威推导；
 * 字段脱敏由后端返回，前端仅展示服务端给的值，不自行推断敏感字段。
 */
import type { AllowedActions } from '@/api/types'

/** 当前数据范围上下文（多组织/多范围场景由登录后角色投影写入） */
export interface DataScopeContext {
  canonicalRole?: string
  dataScope?: string
  objectState?: string
  /** 超管标记：DR-01 关闭后仅超管可执行写操作（is_admin=1） */
  isSuperAdmin?: boolean
}

let scopeContext: DataScopeContext = {}

export function setDataScopeContext(ctx: DataScopeContext): void {
  scopeContext = { ...ctx }
}

export function getDataScopeContext(): DataScopeContext {
  return scopeContext
}

/** 判断 allowed_actions 是否包含指定动作 */
export function hasAction(allowedActions: AllowedActions | undefined, action: string): boolean {
  return Array.isArray(allowedActions) && allowedActions.includes(action)
}

/** 判断 allowed_actions 是否包含任一动作 */
export function hasAnyAction(
  allowedActions: AllowedActions | undefined,
  actions: string[],
): boolean {
  return actions.some((a) => hasAction(allowedActions, a))
}

/**
 * SoD（职责分离）辅助：Actor-level Invariant 由后端强制，
 * 前端仅用于展示「禁止操作」态，不作为安全边界。
 */
export function isActorConflict(
  allowedActions: AllowedActions | undefined,
  action: string,
): boolean {
  return !hasAction(allowedActions, action)
}

/** 脱敏字段辅助：后端约定脱敏值为 null/undefined 或显式 masked 标记时返回 true */
export function isFieldMasked(value: unknown): boolean {
  if (value === null || value === undefined) return false
  if (typeof value === 'object' && 'masked' in (value as Record<string, unknown>)) {
    return Boolean((value as Record<string, unknown>).masked)
  }
  return false
}

/**
 * DR-05 轻量 RBAC：是否有写/敏感操作权限。
 * DR-01 关闭为「仅超管模式」→ 写操作仅 isSuperAdmin 可用，其余返回 false（前端据此置灰并显示原因）。
 * 服务端仍强制校验（allowed_actions + 后端 guardRole），本函数仅用于按钮态展示。
 */
export const WRITE_ACTIONS = ['add', 'edit', 'approve', 'execute']

export function isWritableAction(action: string): boolean {
  return WRITE_ACTIONS.includes(action)
}

export function isSuperAdmin(): boolean {
  return scopeContext.isSuperAdmin === true
}

/**
 * 判定某动作是否可点击：非写动作按 allowed_actions；写动作额外要求超管。
 * reason 用于展示禁用原因（仅超管可用）。
 */
export function canRenderAction(allowedActions: AllowedActions | undefined, action: string): { ok: boolean; reason?: string } {
  if (!hasAction(allowedActions, action)) {
    return { ok: false, reason: '无操作权限' }
  }
  if (isWritableAction(action) && !isSuperAdmin()) {
    return { ok: false, reason: '仅超管可用' }
  }
  return { ok: true }
}
