import type { Component } from 'vue'
import type { PageId } from '@/types/page'

/**
 * 已逐页实现的 Admin 页面组件映射（S03-P03 逐页批次）。
 * - key = 权威 Page ID（33 个之一）。
 * - 未在此注册的 Page 回退到 views/common/ListPage.vue（schema 骨架）。
 * - 逐页实现时，在 src/views/<nav>/ 下落地组件并在此登记即可替换骨架。
 */
export const ADMIN_PAGE_COMPONENTS: Partial<Record<PageId, () => Promise<Component>>> = {
  'A-WORK-001': () => import('@/views/workbench/Overview.vue'),
  'A-WORK-002': () => import('@/views/workbench/Todo.vue'),
  'A-USER-001': () => import('@/views/admission/UserList.vue'),
  'A-USER-002': () => import('@/views/admission/User360.vue'),
  'A-USER-004': () => import('@/views/admission/AssetAdjust.vue'),
  'A-KYC-001': () => import('@/views/admission/KycQueue.vue'),
  'A-LEDGER-001': () => import('@/views/ledger/LedgerOverview.vue'),
  'A-LEDGER-002': () => import('@/views/ledger/LedgerAccounts.vue'),
  'A-LEDGER-003': () => import('@/views/ledger/LedgerPools.vue'),
  'A-LEDGER-004': () => import('@/views/ledger/LedgerCorrections.vue'),
  'A-ROBOT-001': () => import('@/views/robot/RobotList.vue'),
  'A-ROBOT-002': () => import('@/views/robot/RobotDetail.vue'),
  'A-ROBOT-003': () => import('@/views/robot/RobotRewards.vue'),
  'A-OTC-001': () => import('@/views/otc/OtcOrders.vue'),
  'A-OTC-002': () => import('@/views/otc/OtcOrderDetail.vue'),
  'A-POWER-001': () => import('@/views/otc/PowerAccounts.vue'),
  'A-PREDICT-001': () => import('@/views/prediction/MarketList.vue'),
  'A-PREDICT-002': () => import('@/views/prediction/MarketDetail.vue'),
  'A-PREDICT-003': () => import('@/views/prediction/ResultSettlement.vue'),
  'A-PREDICT-004': () => import('@/views/prediction/RefundCorrection.vue'),
  'A-RISK-001': () => import('@/views/risk/RiskCase.vue'),
  'A-APPROVAL-001': () => import('@/views/risk/ApprovalCenter.vue'),
  'A-CONFIG-001': () => import('@/views/risk/ConfigDefinitions.vue'),
  'A-CONFIG-002': () => import('@/views/risk/ConfigReleases.vue'),
  'A-POLICY-001': () => import('@/views/risk/PolicyList.vue'),
  'A-SUPPORT-001': () => import('@/views/support/TicketQueue.vue'),
  'A-SUPPORT-002': () => import('@/views/support/TicketDetail.vue'),
  'A-AUDIT-001': () => import('@/views/support/AuditLogs.vue'),
  'A-OPS-001': () => import('@/views/support/OpsConsole.vue'),
  'A-REPORT-001': () => import('@/views/support/ReportList.vue'),
  'A-GROWTH-001': () => import('@/views/support/GrowthReferral.vue'),
  'A-MIGRATION-001': () => import('@/views/support/MigrationApt.vue'),
  'A-EMERGENCY-001': () => import('@/views/support/EmergencyControl.vue'),
}
