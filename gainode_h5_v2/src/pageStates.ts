/**
 * 页面数据五态标注体系（NEXT-02 步骤①）
 * 设计稿：.project-ai/plans/NEXT-02-STEP1-FIVE-STATE-DESIGN.md
 *
 * Page ID 与 src/router/index.ts 的 meta.pageId 一一对应（防漏页/防死键由
 * tests/page-states.spec.ts 双向校验）。初始状态判定依据设计稿 §4 基线表。
 * FAIL_CLOSED 页面必须携带非空 note（写路径未解冻原因）。
 */

export const PAGE_DATA_STATES = [
  'REAL_DATA',
  'READ_ONLY',
  'FAIL_CLOSED',
  'SKELETON',
  'DEFERRED',
] as const

export type PageDataState = (typeof PAGE_DATA_STATES)[number]

export interface PageStateEntry {
  state: PageDataState
  /** 状态原因说明；FAIL_CLOSED 必填非空（设计稿 §1/§3） */
  note?: string
}

/**
 * 全站页面数据状态注册表。
 * 键 = 路由 meta.pageId（含 COMMON-* 公共页）；共 45 项 = 44 个 m-* 页 + 1 公共受限页。
 */
export const PAGE_STATES: Record<string, PageStateEntry> = {
  // ---- 公共 ----
  'COMMON-RESTRICTED': { state: 'READ_ONLY', note: '受限占位页，无数据交互' },

  // ---- REAL_DATA：已接真实端点页（登录/注册/MFA/KYC 提交/语言设置等） ----
  'M-AUTH-001': { state: 'REAL_DATA', note: '登录已接真实端点' },
  'M-AUTH-002': { state: 'REAL_DATA', note: '注册已接真实端点' },
  'M-AUTH-003': { state: 'REAL_DATA', note: 'OTP 验证已接真实端点' },
  'M-AUTH-004': { state: 'REAL_DATA', note: '恢复流程已接真实端点' },
  'M-AUTH-005': { state: 'REAL_DATA', note: 'MFA 已接真实端点' },
  'M-KYC-001': { state: 'REAL_DATA', note: 'KYC 总览读取真实状态' },
  'M-KYC-002': { state: 'REAL_DATA', note: 'KYC 提交已接真实端点' },
  'M-KYC-003': { state: 'REAL_DATA', note: 'KYC 状态轮询真实端点' },
  'M-SEC-001': { state: 'REAL_DATA', note: '安全设置已接真实端点' },
  'M-SEC-002': { state: 'REAL_DATA', note: '会话管理已接真实端点' },
  'M-SETTINGS-001': { state: 'REAL_DATA', note: '语言设置已接真实端点' },

  // ---- READ_ONLY：只读聚合页（home 行情、notice 列表、asset 流水等） ----
  'M-HOME-001': { state: 'READ_ONLY', note: '行情只读' },
  'M-NOTICE-001': { state: 'READ_ONLY', note: '公告列表只读' },
  'M-ASSET-001': { state: 'READ_ONLY', note: '资产总览只读聚合' },
  'M-ASSET-002': { state: 'READ_ONLY', note: '流水列表只读' },
  'M-ASSET-003': { state: 'READ_ONLY', note: '流水详情只读' },
  'M-POWER-001': { state: 'READ_ONLY', note: '电力展示只读' },
  'M-ME-001': { state: 'READ_ONLY', note: '个人中心聚合展示' },
  'M-ROBOT-001': { state: 'READ_ONLY', note: '机器人总览只读' },
  'M-ROBOT-005': { state: 'READ_ONLY', note: '等级列表只读' },
  'M-ROBOT-007': { state: 'READ_ONLY', note: '活动展示只读' },
  'M-PREDICT-001': { state: 'READ_ONLY', note: '预测首页行情只读' },
  'M-PREDICT-006': { state: 'READ_ONLY', note: '异常状态展示只读' },
  'M-OTC-001': { state: 'READ_ONLY', note: 'OTC 首页行情只读' },
  'M-OTC-005': { state: 'READ_ONLY', note: '我的 OTC 单列表只读' },
  'M-OTC-006': { state: 'READ_ONLY', note: 'OTC 详情只读' },

  // ---- FAIL_CLOSED：写路径未解冻（下单链/升级领取链/迁移），note 必填 ----
  'M-PREDICT-002': { state: 'FAIL_CLOSED', note: '下单写路径未解冻' },
  'M-PREDICT-003': { state: 'FAIL_CLOSED', note: '下单确认写路径未解冻' },
  'M-PREDICT-005': { state: 'FAIL_CLOSED', note: '订单提交写路径未解冻' },
  'M-OTC-002': { state: 'FAIL_CLOSED', note: 'OTC 下单写路径未解冻' },
  'M-OTC-003': { state: 'FAIL_CLOSED', note: 'OTC 确认写路径未解冻' },
  'M-ROBOT-002': { state: 'FAIL_CLOSED', note: '机器人启动写路径未解冻' },
  'M-ROBOT-003': { state: 'FAIL_CLOSED', note: '升级写路径未解冻' },
  'M-ROBOT-004': { state: 'FAIL_CLOSED', note: '升级结果领取写路径未解冻' },
  'M-ROBOT-006': { state: 'FAIL_CLOSED', note: '奖励领取写路径未解冻' },
  'M-MIGRATION-001': { state: 'FAIL_CLOSED', note: '迁移写入未解冻' },
  'M-AI-001': { state: 'FAIL_CLOSED', note: 'AI 信号功能关闭' },

  // ---- SKELETON：结算中/竞猜结果等待类 ----
  'M-PREDICT-004': { state: 'SKELETON', note: '竞猜结果结算等待中' },
  'M-OTC-004': { state: 'SKELETON', note: 'OTC 结果等待中' },
  'M-SUPPORT-001': { state: 'SKELETON', note: '工单后端未接入，骨架占位' },
  'M-SUPPORT-002': { state: 'SKELETON', note: '工单创建后端未接入，骨架占位' },
  'M-SUPPORT-003': { state: 'SKELETON', note: '工单详情后端未接入，骨架占位' },

  // ---- DEFERRED：合同 DEFER 项 ----
  'M-GROWTH-001': { state: 'DEFERRED', note: '团队/邀请为合同 DEFER 项' },
  'M-PREDICT-FREE-001': { state: 'DEFERRED', note: '免费预测互动为合同 DEFER 项' },
}

/** 按 pageId 查询注册项（大小写不敏感兜底）；未注册返回 undefined。 */
export function getPageState(pageId: string): PageStateEntry | undefined {
  if (!pageId) return undefined
  return PAGE_STATES[pageId] ?? PAGE_STATES[pageId.toUpperCase()]
}
