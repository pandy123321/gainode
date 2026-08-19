/**
 * Admin 8 导航 route registry + 33 Page ID 注册表（S03-P03-P03）。
 * 权威来源：04_ADMIN_HIFI_PROTOTYPE_SPEC_V2.2.md §2/§3、07 §9 S03-P03 逐导航批次表。
 *
 * 用途：
 * - P04/P05 逐页实现时，据此生成 route、挂载 Page ID meta、校验完整性。
 * - 菜单由 RBAC 过滤（store/user.menus），但本表是「页面全集」，
 *   直接 URL 命中时仍需服务端授权（requireServerAuth）。
 */
import type { AdminNavId, AdminPageMeta, PageId } from '@/types/page'

/** 8 个一级导航（顺序与 04 §2 严格一致） */
export interface AdminNav {
  id: AdminNavId
  /** 一级导航标题（i18n key 前缀） */
  title: string
  /** 顺序 */
  order: number
}

export const ADMIN_NAVS: AdminNav[] = [
  { id: 'workbench', title: '工作台', order: 1 },
  { id: 'admission', title: '用户与准入', order: 2 },
  { id: 'ledger', title: '资产与账本', order: 3 },
  { id: 'robot', title: '机器人与权益', order: 4 },
  { id: 'otc-power', title: 'OTC 与 Power', order: 5 },
  { id: 'prediction', title: '赛事预测', order: 6 },
  { id: 'risk-governance', title: '风控 / 审批 / 参数 / 策略', order: 7 },
  { id: 'support-audit-ops', title: '客服 / 审计 / 运维', order: 8 },
]

/** 33 个 Admin Page ID 注册表（全集） */
export const ADMIN_PAGE_REGISTRY: AdminPageMeta[] = [
  // 01 工作台
  { pageId: 'A-WORK-001', navId: 'workbench', title: '运营总览', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },
  { pageId: 'A-WORK-002', navId: 'workbench', title: '今日待办', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },

  // 02 用户与准入
  { pageId: 'A-USER-001', navId: 'admission', title: '用户列表', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },
  { pageId: 'A-USER-002', navId: 'admission', title: '用户 360', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },
  { pageId: 'A-USER-004', navId: 'admission', title: '资产调整', priority: 'P1_CONDITIONAL', contractStatus: 'CONTRACT_GAP', requireServerAuth: true },
  { pageId: 'A-KYC-001', navId: 'admission', title: 'KYC 审核队列', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },

  // 03 资产与账本
  { pageId: 'A-LEDGER-001', navId: 'ledger', title: '资产总览', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },
  { pageId: 'A-LEDGER-002', navId: 'ledger', title: 'APT 账户与流水', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },
  { pageId: 'A-LEDGER-003', navId: 'ledger', title: '池子与对账', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },
  { pageId: 'A-LEDGER-004', navId: 'ledger', title: '更正 / 冲正申请', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },

  // 04 机器人与权益
  { pageId: 'A-ROBOT-001', navId: 'robot', title: 'Robot 列表', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },
  { pageId: 'A-ROBOT-002', navId: 'robot', title: 'Robot 详情', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },
  { pageId: 'A-ROBOT-003', navId: 'robot', title: 'Reward / Claim 运营', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },

  // 05 OTC 与 Power
  { pageId: 'A-OTC-001', navId: 'otc-power', title: 'OTC 订单列表', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },
  { pageId: 'A-OTC-002', navId: 'otc-power', title: 'OTC 订单详情 / 审核', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },
  { pageId: 'A-POWER-001', navId: 'otc-power', title: 'Power 账户与流水', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },

  // 06 赛事预测
  { pageId: 'A-PREDICT-001', navId: 'prediction', title: 'Market / Event 列表', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },
  { pageId: 'A-PREDICT-002', navId: 'prediction', title: 'Market 详情', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },
  { pageId: 'A-PREDICT-003', navId: 'prediction', title: 'Result / Settlement', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },
  { pageId: 'A-PREDICT-004', navId: 'prediction', title: 'Refund / Correction', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },

  // 07 风控 / 审批 / 参数 / 策略
  { pageId: 'A-RISK-001', navId: 'risk-governance', title: 'Risk Case', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },
  { pageId: 'A-APPROVAL-001', navId: 'risk-governance', title: '审批中心', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },
  { pageId: 'A-CONFIG-001', navId: 'risk-governance', title: 'Parameter Center · Definition/Candidate', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },
  { pageId: 'A-CONFIG-002', navId: 'risk-governance', title: 'Parameter Release / Snapshot', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },
  { pageId: 'A-POLICY-001', navId: 'risk-governance', title: '地区 / KYC / 保护策略', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },

  // 08 客服 / 审计 / 运维
  { pageId: 'A-SUPPORT-001', navId: 'support-audit-ops', title: '工单队列', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },
  { pageId: 'A-SUPPORT-002', navId: 'support-audit-ops', title: '工单详情', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },
  { pageId: 'A-AUDIT-001', navId: 'support-audit-ops', title: '审计日志', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },
  { pageId: 'A-OPS-001', navId: 'support-audit-ops', title: '异步任务 / 对账 / 系统状态', priority: 'P0', contractStatus: 'OPEN', requireServerAuth: true },
  { pageId: 'A-REPORT-001', navId: 'support-audit-ops', title: '运营报表', priority: 'P1', contractStatus: 'TBC', requireServerAuth: true },
  { pageId: 'A-GROWTH-001', navId: 'support-audit-ops', title: 'Referral / Team 运营', priority: 'P1', contractStatus: 'TBC', requireServerAuth: true },
  { pageId: 'A-MIGRATION-001', navId: 'support-audit-ops', title: 'APT Migration', priority: 'FUTURE', contractStatus: 'CLOSED', requireServerAuth: true },
  { pageId: 'A-EMERGENCY-001', navId: 'support-audit-ops', title: '紧急操作控制', priority: 'P0', contractStatus: 'TBC', requireServerAuth: true },
]

/** 按导航分组查询 */
export function pagesByNav(navId: AdminNavId): AdminPageMeta[] {
  return ADMIN_PAGE_REGISTRY.filter((p) => p.navId === navId)
}

/** 按 pageId 查询 */
export function pageById(pageId: PageId): AdminPageMeta | undefined {
  return ADMIN_PAGE_REGISTRY.find((p) => p.pageId === pageId)
}

/**
 * 完整性自检：33 个 Page ID 恰好各注册一次，无遗漏、无重复、无未知导航。
 * 返回错误列表；空数组表示通过。
 */
export function validateRegistry(): string[] {
  const errors: string[] = []
  const seen = new Set<PageId>()
  const navIds = new Set(ADMIN_NAVS.map((n) => n.id))

  for (const page of ADMIN_PAGE_REGISTRY) {
    if (seen.has(page.pageId)) {
      errors.push(`重复注册 Page ID: ${page.pageId}`)
    }
    seen.add(page.pageId)
    if (!navIds.has(page.navId)) {
      errors.push(`未知导航: ${page.navId}（${page.pageId}）`)
    }
  }

  // 权威 33 个 Page ID 全集（字面量联合来源见 types/page.ts）
  const ALL_PAGE_IDS: PageId[] = [
    'A-WORK-001', 'A-WORK-002',
    'A-USER-001', 'A-USER-002', 'A-USER-004', 'A-KYC-001',
    'A-LEDGER-001', 'A-LEDGER-002', 'A-LEDGER-003', 'A-LEDGER-004',
    'A-ROBOT-001', 'A-ROBOT-002', 'A-ROBOT-003',
    'A-OTC-001', 'A-OTC-002', 'A-POWER-001',
    'A-PREDICT-001', 'A-PREDICT-002', 'A-PREDICT-003', 'A-PREDICT-004',
    'A-RISK-001', 'A-APPROVAL-001', 'A-CONFIG-001', 'A-CONFIG-002', 'A-POLICY-001',
    'A-SUPPORT-001', 'A-SUPPORT-002', 'A-AUDIT-001', 'A-OPS-001',
    'A-REPORT-001', 'A-GROWTH-001', 'A-MIGRATION-001', 'A-EMERGENCY-001',
  ]

  for (const id of ALL_PAGE_IDS) {
    if (!seen.has(id)) {
      errors.push(`缺失 Page ID: ${id}`)
    }
  }

  return errors
}

export const ADMIN_PAGE_COUNT = ADMIN_PAGE_REGISTRY.length
