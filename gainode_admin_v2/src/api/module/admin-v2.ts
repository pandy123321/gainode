/**
 * Admin 2.0 V2 API 模块（对接后端 /api/v1/admin/...，OPTION_A）。
 *
 * 只读对接：列表/详情/聚合，经 http-v2.ts（Envelope 解包 + 六请求头 + 刷新）。
 * 字段口径：后端 OpenAPI DTO；金额为 string decimal（前端不做 number 转换丢失精度）。
 * 写路径（市场/结算/审批等）：admin 角色映射未冻结，后端保持 fail-closed（503），
 *   本模块不提供写方法（避免前端本地推导可操作性）。
 */
import { get } from '../http-v2'
import type { Envelope } from '../types'

// ---- 通用分页响应 ----
export interface PageData<T> {
  total: number
  page: number
  size: number
}

// ---- 用户（A-USER-001） ----
export interface AdminUserDto {
  user_id: string
  user_no: string
  account: string
  email: string | null
  phone: string | null
  nickname: string | null
  is_verify: number
  status: number
  balance_apt: string
  robots: Array<{ robot_id: string; level: number; status: string }>
  created_time: number
}
export function listUsers(params: {
  page: number
  size: number
  keyword?: string
}): Promise<Envelope<PageData<AdminUserDto> & { users: AdminUserDto[] }>> {
  return get('/api/v1/admin/admission/users', { params })
}

// ---- OTC 订单列表（A-OTC-001）/ 详情（A-OTC-002） ----
export interface AdminOtcOrderDto {
  otc_order_id: string
  user_id: string
  side: string
  price: string
  quantity_apt: string
  filled_quantity_apt: string
  remaining_quantity_apt: string
  fee_apt: string
  power_required: string
  power_frozen: string
  status: string
  rule_version: string
  created_time: number
}
export function listOtcOrders(params: {
  page: number
  size: number
  status?: string
}): Promise<Envelope<PageData<AdminOtcOrderDto> & { orders: AdminOtcOrderDto[] }>> {
  return get('/api/v1/admin/otc/orders', { params })
}
export function getOtcOrder(id: string): Promise<Envelope<Record<string, unknown>>> {
  return get(`/api/v1/admin/otc/orders/${id}`)
}

// ---- Robot 列表（A-ROBOT-001）/ 详情（A-ROBOT-002） ----
export interface AdminRobotDto {
  robot_id: string
  user_id: string
  level: number
  status: string
  standard_capacity: string
  rule_version: string
  parameter_release_id: string
  object_version: number
  created_time: number
}
export function listRobots(params: {
  page: number
  size: number
  status?: string
}): Promise<Envelope<PageData<AdminRobotDto> & { robots: AdminRobotDto[] }>> {
  return get('/api/v1/admin/robot/list', { params })
}
export function getRobot(id: string): Promise<Envelope<Record<string, unknown>>> {
  return get(`/api/v1/admin/robot/${id}`)
}

// ---- Reward 运营（A-ROBOT-003） ----
export interface AdminRewardDto {
  reward_id: string
  user_id: string
  robot_id: string
  period: string
  standard_capacity: string
  daily_reward_coefficient: string
  quantity_apt: string
  state: string
  claim_id: string
  ledger_entry_id: string
  expires_at: number
  rule_version: string
  created_time: number
}
export function listRewards(params: {
  page: number
  size: number
  state?: string
}): Promise<Envelope<PageData<AdminRewardDto> & { rewards: AdminRewardDto[] }>> {
  return get('/api/v1/admin/robot/rewards', { params })
}

// ---- 工单队列（A-SUPPORT-001）/ 详情（A-SUPPORT-002） ----
export interface AdminTicketDto {
  ticket_id: string
  user_id: string
  category: string
  status: string
  assigned_to: string | null
  last_activity_at: number
  resolution_type: string | null
  created_time: number
}
export function listTickets(params: {
  page: number
  size: number
  status?: string
}): Promise<Envelope<PageData<AdminTicketDto> & { tickets: AdminTicketDto[] }>> {
  return get('/api/v1/admin/support/tickets', { params })
}
export function getTicket(id: string): Promise<Envelope<Record<string, unknown>>> {
  return get(`/api/v1/admin/support/tickets/${id}`)
}

// ---- APT 账户（A-LEDGER-002） ----
export interface AdminLedgerAccountDto {
  account_id: string
  user_id: string
  balance_apt_i: string
  balance_apt_c: string
  frozen_apt_i: string
  frozen_apt_c: string
  total_earned_apt: string
  total_spent_apt: string
  effective_available: string
  rule_version: string
  created_time: number
}
export function listLedgerAccounts(params: {
  page: number
  size: number
  keyword?: string
}): Promise<Envelope<PageData<AdminLedgerAccountDto> & { accounts: AdminLedgerAccountDto[] }>> {
  return get('/api/v1/admin/ledger/accounts', { params })
}

// ---- APT 流水明细（A-LEDGER-002 明细） ----
export interface AdminLedgerEntryDto {
  ledger_entry_id: string
  account_id: string
  asset: string
  quantity: string
  entry_direction: number
  entry_type: string
  state: string
  source_object_type: string
  source_object_id: string
  journal_batch_id: string
  reversal_of: string
  rule_version: string
  snapshot_id: string
  audit_event_id: string
  created_time: number
}
export function listLedgerEntries(params: {
  page: number
  size: number
  account_id?: string
}): Promise<Envelope<PageData<AdminLedgerEntryDto> & { entries: AdminLedgerEntryDto[] }>> {
  return get('/api/v1/admin/ledger/entries', { params })
}

// ---- Risk Case（A-RISK-001） ----
export interface AdminRiskCaseDto {
  case_id: string
  user_id: string
  risk_type: string
  severity: string
  status: string
  detected_at: number
  detected_by: string
  reviewed_by: string | null
  disposition: string | null
  appeal_eligible: number
  created_time: number
}
export function listRiskCases(params: {
  page: number
  size: number
  status?: string
  severity?: string
}): Promise<Envelope<PageData<AdminRiskCaseDto> & { cases: AdminRiskCaseDto[] }>> {
  return get('/api/v1/admin/risk/cases', { params })
}

// ---- 审批中心（A-APPROVAL-001） ----
export interface AdminApprovalTaskDto {
  approval_id: string
  request_type: string
  request_object_type: string
  request_object_id: string
  status: string
  submitted_by: string
  submitter_role: string
  assigned_to: string | null
  decided_by: string | null
  decided_at: number
  created_time: number
}
export function listApprovalTasks(params: {
  page: number
  size: number
  status?: string
}): Promise<Envelope<PageData<AdminApprovalTaskDto> & { tasks: AdminApprovalTaskDto[] }>> {
  return get('/api/v1/admin/approval/tasks', { params })
}

// ---- Parameter Center（A-CONFIG-001） ----
export interface AdminParameterReleaseDto {
  release_id: string
  parameter_keys: string | null
  status: string
  draft_version: string
  approved_by: string | null
  scheduled_at: number
  activated_at: number
  snapshot_id: string
  created_time: number
}
export function listParameterReleases(params: {
  page: number
  size: number
  status?: string
}): Promise<Envelope<PageData<AdminParameterReleaseDto> & { releases: AdminParameterReleaseDto[] }>> {
  return get('/api/v1/admin/parameter/definitions', { params })
}

// ---- Market（A-PREDICT-001） ----
export interface AdminPredictionMarketDto {
  market_id: string
  event_id: string
  template_id: string
  market_status: string
  lock_at: number
  selections: string | null
  result_status: string | null
  rule_version: string
  parameter_release_id: string
  created_time: number
}
export function listPredictionMarkets(params: {
  page: number
  size: number
  status?: string
}): Promise<Envelope<PageData<AdminPredictionMarketDto> & { markets: AdminPredictionMarketDto[] }>> {
  return get('/api/v1/admin/prediction/markets', { params })
}

// ---- Power 账户（A-POWER-001） ----
export interface AdminPowerAccountDto {
  user_id: string
  available: string
  frozen: string
  consumed_period: string
  released_period: string
  recovering: string
  limit: string
  power_cap_source_robot_level: number
  last_restore_at: number
  next_restore_at: number
  rule_version: string
  created_time: number
}
export function listPowerAccounts(params: {
  page: number
  size: number
}): Promise<Envelope<PageData<AdminPowerAccountDto> & { accounts: AdminPowerAccountDto[] }>> {
  return get('/api/v1/admin/power/accounts', { params })
}

// ---- 审计日志（A-AUDIT-001） ----
export interface AdminAuditEventDto {
  audit_event_id: string
  event_code: string
  actor_id: string
  actor_role: string
  target_object_type: string
  target_object_id: string
  outcome: string
  reason_code: string
  request_id: string
  approval_id: string
  case_id: string
  created_time: number
}
export function listAuditLog(params?: Record<string, string | number>): Promise<
  Envelope<{ audit_events: AdminAuditEventDto[] }>
> {
  return get('/api/v1/admin/audit-log', { params })
}
export function getAuditLogDetail(id: string): Promise<Envelope<Record<string, unknown>>> {
  return get(`/api/v1/admin/audit-log/${id}`)
}

// ---- 资产总览（A-LEDGER-001） ----
export interface AdminLedgerOverview {
  account_count: number
  total_balance_apt_i: string
  total_frozen_apt_i: string
  total_earned_apt: string
  total_spent_apt: string
}
export function getLedgerOverview(): Promise<Envelope<AdminLedgerOverview>> {
  return get('/api/v1/admin/ledger/overview')
}

// ---- 工作台运营总览（A-WORK-001） ----
export interface AdminWorkbenchOverview {
  user_count: number
  robot_count: number
  otc_open_orders: number
  market_count: number
  pending_approvals: number
}
export function getWorkbenchOverview(): Promise<Envelope<AdminWorkbenchOverview>> {
  return get('/api/v1/admin/workbench/overview')
}
