/**
 * Admin 页面与导航类型契约（S03-P03-P03）。
 * 权威来源：04_ADMIN_HIFI_PROTOTYPE_SPEC_V2.2.md（8 Root IA + 33 Page IDs）
 *         07_DEVELOPMENT_AND_ACCEPTANCE.md §9 S03-P03 逐导航批次表。
 *
 * 授权模型（04 §12 / 05 §11）：canonical_role + data_scope + object_state +
 * allowed_actions + risk_policy + SoD。页面注册不等于授权；直接 URL 仍需服务端授权。
 */

/** 8 个一级导航 ID（与 04 §2 顺序严格一致） */
export type AdminNavId =
  | 'workbench' // 01 工作台
  | 'admission' // 02 用户与准入
  | 'ledger' // 03 资产与账本
  | 'robot' // 04 机器人与权益
  | 'otc-power' // 05 OTC 与 Power
  | 'prediction' // 06 赛事预测
  | 'risk-governance' // 07 风控 / 审批 / 参数 / 策略
  | 'support-audit-ops' // 08 客服 / 审计 / 运维

/** 33 个权威 Admin Page ID（字面量联合，逐页实现的唯一标识） */
export type PageId =
  | 'A-WORK-001'
  | 'A-WORK-002'
  | 'A-USER-001'
  | 'A-USER-002'
  | 'A-USER-004'
  | 'A-KYC-001'
  | 'A-LEDGER-001'
  | 'A-LEDGER-002'
  | 'A-LEDGER-003'
  | 'A-LEDGER-004'
  | 'A-ROBOT-001'
  | 'A-ROBOT-002'
  | 'A-ROBOT-003'
  | 'A-OTC-001'
  | 'A-OTC-002'
  | 'A-POWER-001'
  | 'A-PREDICT-001'
  | 'A-PREDICT-002'
  | 'A-PREDICT-003'
  | 'A-PREDICT-004'
  | 'A-RISK-001'
  | 'A-APPROVAL-001'
  | 'A-CONFIG-001'
  | 'A-CONFIG-002'
  | 'A-POLICY-001'
  | 'A-SUPPORT-001'
  | 'A-SUPPORT-002'
  | 'A-AUDIT-001'
  | 'A-OPS-001'
  | 'A-REPORT-001'
  | 'A-GROWTH-001'
  | 'A-MIGRATION-001'
  | 'A-EMERGENCY-001'

/**
 * 7 个 DEFERRED 页（占位不 404，不计入 S03-P03 验收）。
 * 来源：V2.4.1 Page Map 的 P1_CONDITIONAL 页，07 §8 显式注册表未纳入。
 */
export type DeferredPageId =
  | 'A-AI-001'
  | 'A-AI-002'
  | 'A-AI-004'
  | 'A-DATA-002'
  | 'A-DATA-003'
  | 'A-DATA-004'
  | 'A-DATA-005'

/** route meta 里可出现的全部页面标识（权威 33 + DEFERRED 7） */
export type AnyAdminPageId = PageId | DeferredPageId

/** 页面优先级（04 §3 标题标注；DEFERRED 为占位保留，不计入验收） */
export type PagePriority = 'P0' | 'P1' | 'P1_CONDITIONAL' | 'FUTURE' | 'DEFERRED'

/** 页面合同状态（决定页面是完整实现 / 仅预览 / 关闭占位） */
export type PageContractStatus = 'OPEN' | 'CONTRACT_GAP' | 'CLOSED' | 'TBC'

/** 页面注册元数据（route meta 扩展 + registry 共用） */
export interface AdminPageMeta {
  /** 页面唯一标识 */
  pageId: PageId
  /** 所属一级导航 */
  navId: AdminNavId
  /** 页面标题（i18n key 前缀，实际文案走 7 语言） */
  title: string
  /** 优先级 */
  priority: PagePriority
  /** 合同状态 */
  contractStatus: PageContractStatus
  /** 是否需服务端授权（直接 URL 也由服务端强制，前端 RBAC 过滤仅用于菜单展示） */
  requireServerAuth: boolean
}

/** 写页七态（04 §2 / 07 §9 S03-P03 步骤 4）：在 AdminFiveState 基础上追加 */
export type WriteStateName =
  | 'invalid'
  | 'confirm'
  | 'submitting'
  | 'processing'
  | 'success'
  | 'failed'
  | 'stateChanged'

declare module 'vue-router' {
  interface RouteMeta {
    /** 页面标识（权威 33 + DEFERRED 7） */
    pageId?: AnyAdminPageId
    /** 所属导航 */
    navId?: AdminNavId
    /** 优先级 */
    priority?: PagePriority
    /** 合同状态 */
    contractStatus?: PageContractStatus
    /** 是否需服务端授权 */
    requireServerAuth?: boolean
  }
}
