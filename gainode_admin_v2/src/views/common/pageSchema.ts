// Gainode 2.0 后台通用列表页 schema（骨架配置）
// key = 前端路由 path，与 sql/20260817_admin_20_menu_seed.sql 的 route_url 一致
// 字段/筛选/统计设计参考：
//   * GAINODE_ADMIN_PAGE_MAP_V2.4.1.md（页面导航树 / PageID）
//   * Gainode_Admin_Prototype_Planning_V2.4.1_CN.md
//       - §11.2 用户管理：UID/手机号/邮箱/注册方式/直推/上级/推荐码/Robot状态/KYC/APT/Power
//       - §11.3 用户限制：账户/余额/OTC/Robot 分别控制
//       - §11.4 资产调整：Ledger Based / Append-only / Correction / Journal
//       - §11.5 AI：AI_SIGNAL/AI_ANALYSIS/AI_RECOMMENDATION/AI_SIMULATION/HUMAN_IN_LOOP
//       - §11.6 OTC：CONTROLLED_MATCHING
//       - §11.7 Power：Available/Frozen/Consumed/Released/Recovering/Cap
//       - §11.8 Audit：Actor/Action/Object/BeforeAfter/Reason/Evidence
//       - §12 视觉：数字右对齐 / Sticky Filter
//
// 【字段口径原则（重要）】
//   本文件的 columns/filters/stats 仅为「UI 占位骨架」，最终字段口径以 07 §8 的
//   OpenAPI DTO 为准（聚合多表投影 + 脱敏 + 字符串 decimal + UTC 时间）。
//   - 不要照搬 V1.x 的 sys_table_field 表字段直映射（语义不匹配，见 ADMIN_S03P03_HANDOFF.md §3.1）。
//   - 逐页联调时，本文件的字段（prop/label/类型）可随 OpenAPI DTO 微调，不算违约。
//   - 金额/数量字段务必保留字符串 decimal 语义，不要在前端转成 number 丢失精度。
//
// 说明：2.0 后端 HTTP 接口尚未实现（STAGE-02 仅域对象），此处仅为 UI 骨架，数据为空。
//       接入真实接口时，由前端同事按 DTO 口径对接即可。

export interface ColumnDef {
  prop: string;
  label: string;
  width?: number;
  minWidth?: number;
  /** 对齐方式；金额/数量/数字列用 right（对齐 §12 数字右对齐） */
  align?: 'left' | 'center' | 'right';
}

export interface StatDef {
  label: string;
  value: string;
}

export interface FilterOption {
  label: string;
  value: string;
}

export type FilterDef =
  | { type: 'select'; prop: string; label: string; options: FilterOption[]; multiple?: boolean }
  | { type: 'daterange'; prop: string; label: string }
  | { type: 'input'; prop: string; label: string; placeholder?: string };

export interface PageSchema {
  /** dashboard=看板；list=纯列表（均支持顶部统计卡片） */
  type: 'dashboard' | 'list';
  /** 搜索框占位文案 */
  searchPlaceholder?: string;
  /** 顶部统计卡片 */
  stats?: StatDef[];
  /** 搜索栏筛选条件（关键词框固定存在，此处为附加筛选） */
  filters?: FilterDef[];
  /** 表格列定义 */
  columns: ColumnDef[];
}

const money = { align: 'right' as const };

/** 时间范围筛选（统一 prop） */
const range = (label = '时间范围'): FilterDef => ({ type: 'daterange', prop: 'time', label });

export const pageSchema: Record<string, PageSchema> = {
  // ================= 工作台（A-WORK） =================
  '/workbench/overview': {
    type: 'dashboard',
    stats: [
      { label: '今日新增用户', value: '--' },
      { label: '今日交易额', value: '--' },
      { label: '今日竞猜额', value: '--' },
      { label: '待审批事项', value: '--' },
      { label: '系统异常', value: '--' },
    ],
    searchPlaceholder: '搜索动态',
    filters: [
      {
        type: 'select', prop: 'feedType', label: '动态类型',
        options: [
          { label: '用户动态', value: 'user' },
          { label: '交易动态', value: 'trade' },
          { label: '风险告警', value: 'risk' },
          { label: '系统通知', value: 'system' },
        ],
      },
      range(),
    ],
    columns: [
      { prop: 'time', label: '时间', width: 180 },
      { prop: 'type', label: '类型', width: 140 },
      { prop: 'content', label: '内容' },
      { prop: 'operator', label: '操作人', width: 120 },
    ],
  },
  '/workbench/todo': {
    type: 'list',
    stats: [
      { label: '今日待办', value: '--' },
      { label: '待处理', value: '--' },
      { label: '处理中', value: '--' },
      { label: '已逾期', value: '--' },
    ],
    searchPlaceholder: '搜索待办事项',
    filters: [
      {
        type: 'select', prop: 'todoType', label: '类型',
        options: [
          { label: '审批', value: 'approval' },
          { label: '结算', value: 'settlement' },
          { label: '风险处理', value: 'risk' },
          { label: '工单', value: 'support' },
        ],
      },
      {
        type: 'select', prop: 'priority', label: '优先级',
        options: [
          { label: '紧急', value: 'urgent' },
          { label: '高', value: 'high' },
          { label: '中', value: 'medium' },
          { label: '低', value: 'low' },
        ],
      },
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '待处理', value: 'pending' },
          { label: '处理中', value: 'processing' },
          { label: '已完成', value: 'done' },
          { label: '已逾期', value: 'overdue' },
        ],
      },
      range('截止时间'),
    ],
    columns: [
      { prop: 'type', label: '类型', width: 140 },
      { prop: 'title', label: '事项' },
      { prop: 'priority', label: '优先级', width: 100 },
      { prop: 'deadline', label: '截止时间', width: 180 },
      { prop: 'status', label: '状态', width: 110 },
    ],
  },

  // ================= 用户管理（A-USER/A-KYC/A-SUPPORT） =================
  // §11.2 权威字段：UID/手机号/邮箱/注册方式/直推/上级/推荐码/Robot状态/KYC/APT/Power
  '/admission/users': {
    type: 'list',
    stats: [
      { label: '总用户数', value: '--' },
      { label: '今日新增', value: '--' },
      { label: '活跃用户', value: '--' },
      { label: 'KYC 待审', value: '--' },
    ],
    searchPlaceholder: '搜索 UID / 手机号 / 邮箱 / 推荐码',
    filters: [
      {
        type: 'select', prop: 'registerType', label: '注册方式',
        options: [
          { label: '手机号', value: 'mobile' },
          { label: '邮箱', value: 'email' },
          { label: '第三方', value: 'oauth' },
        ],
      },
      {
        type: 'select', prop: 'kycStatus', label: 'KYC',
        options: [
          { label: '未认证', value: 'none' },
          { label: '待审核', value: 'pending' },
          { label: '已认证', value: 'approved' },
          { label: '已驳回', value: 'rejected' },
        ],
      },
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '正常', value: 'normal' },
          { label: '受限', value: 'restricted' },
          { label: '冻结', value: 'frozen' },
          { label: '注销', value: 'closed' },
        ],
      },
      range('注册时间'),
    ],
    columns: [
      { prop: 'uid', label: '用户 ID', width: 120 },
      { prop: 'mobile', label: '手机号', width: 130 },
      { prop: 'email', label: '邮箱', width: 180 },
      { prop: 'registerType', label: '注册方式', width: 100 },
      { prop: 'inviteCode', label: '推荐码', width: 120 },
      { prop: 'directParent', label: '直推上级', width: 110 },
      { prop: 'parentAgent', label: '上级代理', width: 110 },
      { prop: 'robotStatus', label: 'Robot 状态', width: 110 },
      { prop: 'kycStatus', label: 'KYC', width: 90 },
      { prop: 'aptBalance', label: 'APT 余额', width: 120, ...money },
      { prop: 'powerBalance', label: 'Power', width: 110, ...money },
      { prop: 'status', label: '状态', width: 90 },
      { prop: 'createdAt', label: '注册时间', width: 170 },
    ],
  },
  '/admission/kyc': {
    type: 'list',
    stats: [
      { label: '待审核', value: '--' },
      { label: '已通过', value: '--' },
      { label: '已驳回', value: '--' },
      { label: '今日提交', value: '--' },
    ],
    searchPlaceholder: '搜索 UID / 姓名 / 证件号',
    filters: [
      {
        type: 'select', prop: 'status', label: '审核状态',
        options: [
          { label: '待审核', value: 'pending' },
          { label: '审核中', value: 'reviewing' },
          { label: '已通过', value: 'approved' },
          { label: '已驳回', value: 'rejected' },
        ],
      },
      {
        type: 'select', prop: 'level', label: 'KYC 等级',
        options: [
          { label: 'L1', value: 'L1' },
          { label: 'L2', value: 'L2' },
          { label: 'L3', value: 'L3' },
        ],
      },
      range('提交时间'),
    ],
    columns: [
      { prop: 'uid', label: '用户 ID', width: 120 },
      { prop: 'realName', label: '姓名', width: 120 },
      { prop: 'idType', label: '证件类型', width: 110 },
      { prop: 'idNo', label: '证件号', width: 190 },
      { prop: 'level', label: 'KYC 等级', width: 100 },
      { prop: 'status', label: '审核状态', width: 110 },
      { prop: 'reviewer', label: '审核人', width: 110 },
      { prop: 'submittedAt', label: '提交时间', width: 170 },
      { prop: 'reviewedAt', label: '审核时间', width: 170 },
    ],
  },
  // §11.3 账户/余额/OTC/Robot 分别控制，不得合并为 Disable User
  '/admission/user-360': {
    type: 'list',
    stats: [
      { label: '受限账户', value: '--' },
      { label: '余额受限', value: '--' },
      { label: 'OTC 受限', value: '--' },
      { label: 'Robot 受限', value: '--' },
    ],
    searchPlaceholder: '搜索 UID / 限制类型',
    filters: [
      {
        type: 'select', prop: 'restrictType', label: '限制类型',
        options: [
          { label: '账户', value: 'account' },
          { label: '余额', value: 'balance' },
          { label: 'OTC', value: 'otc' },
          { label: 'Robot', value: 'robot' },
        ],
      },
      range('限制时间'),
    ],
    columns: [
      { prop: 'uid', label: '用户 ID', width: 120 },
      { prop: 'accountRestrict', label: '账户限制', width: 100 },
      { prop: 'balanceRestrict', label: '余额限制', width: 100 },
      { prop: 'otcRestrict', label: 'OTC 限制', width: 100 },
      { prop: 'robotRestrict', label: 'Robot 限制', width: 110 },
      { prop: 'reason', label: '原因', width: 160 },
      { prop: 'operator', label: '操作人', width: 110 },
      { prop: 'createdAt', label: '限制时间', width: 170 },
      { prop: 'expireAt', label: '到期时间', width: 170 },
    ],
  },
  '/support/tickets': {
    type: 'list',
    stats: [
      { label: '待处理', value: '--' },
      { label: '处理中', value: '--' },
      { label: '已解决', value: '--' },
      { label: '今日新增', value: '--' },
    ],
    searchPlaceholder: '搜索工单号 / UID',
    filters: [
      {
        type: 'select', prop: 'category', label: '分类',
        options: [
          { label: '账户问题', value: 'account' },
          { label: '资产问题', value: 'asset' },
          { label: 'OTC 问题', value: 'otc' },
          { label: '竞猜问题', value: 'predict' },
          { label: '其他', value: 'other' },
        ],
      },
      {
        type: 'select', prop: 'priority', label: '优先级',
        options: [
          { label: '紧急', value: 'urgent' },
          { label: '高', value: 'high' },
          { label: '中', value: 'medium' },
          { label: '低', value: 'low' },
        ],
      },
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '待处理', value: 'pending' },
          { label: '处理中', value: 'processing' },
          { label: '已解决', value: 'resolved' },
          { label: '已关闭', value: 'closed' },
        ],
      },
      range('创建时间'),
    ],
    columns: [
      { prop: 'ticketNo', label: '工单号', width: 170 },
      { prop: 'uid', label: '用户', width: 120 },
      { prop: 'category', label: '分类', width: 120 },
      { prop: 'priority', label: '优先级', width: 100 },
      { prop: 'assignee', label: '处理人', width: 110 },
      { prop: 'status', label: '状态', width: 100 },
      { prop: 'createdAt', label: '创建时间', width: 170 },
      { prop: 'updatedAt', label: '更新时间', width: 170 },
    ],
  },

  // ================= 代理管理（A-AFF） =================
  '/growth/referral': {
    type: 'list',
    stats: [
      { label: '代理总数', value: '--' },
      { label: '活跃代理', value: '--' },
      { label: '今日新增', value: '--' },
      { label: '团队总用户', value: '--' },
    ],
    searchPlaceholder: '搜索代理 ID / 名称',
    filters: [
      {
        type: 'select', prop: 'level', label: '等级',
        options: [
          { label: '一级', value: 'L1' },
          { label: '二级', value: 'L2' },
          { label: '三级', value: 'L3' },
        ],
      },
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '正常', value: 'normal' },
          { label: '暂停', value: 'paused' },
          { label: '停用', value: 'disabled' },
        ],
      },
      range('加入时间'),
    ],
    columns: [
      { prop: 'agentId', label: '代理 ID', width: 120 },
      { prop: 'name', label: '名称', width: 140 },
      { prop: 'level', label: '等级', width: 90 },
      { prop: 'parentAgent', label: '上级代理', width: 110 },
      { prop: 'directUser', label: '直属用户', width: 100, ...money },
      { prop: 'teamUser', label: '团队用户', width: 100, ...money },
      { prop: 'commission', label: '累计佣金', width: 120, ...money },
      { prop: 'status', label: '状态', width: 90 },
      { prop: 'createdAt', label: '加入时间', width: 170 },
    ],
  },

  // ================= 财务管理（A-LEDGER/A-ECON/A-POWER） =================
  '/ledger/overview': {
    type: 'dashboard',
    stats: [
      { label: 'APT 发行总量', value: '--' },
      { label: 'APT 流通量', value: '--' },
      { label: 'Power 发行总量', value: '--' },
      { label: '今日结算额', value: '--' },
      { label: '池子余额', value: '--' },
    ],
    searchPlaceholder: '搜索账户 / 账本',
    filters: [
      {
        type: 'select', prop: 'ledger', label: '账本',
        options: [
          { label: 'APT 主账本', value: 'apt_main' },
          { label: '奖励账本', value: 'reward' },
          { label: '池子账本', value: 'pool' },
          { label: 'Power 账本', value: 'power' },
        ],
      },
      range(),
    ],
    columns: [
      { prop: 'ledger', label: '账本', width: 140 },
      { prop: 'type', label: '类型', width: 120 },
      { prop: 'balance', label: '余额', width: 160, ...money },
      { prop: 'updatedAt', label: '更新时间', width: 170 },
    ],
  },
  // §11.4 Ledger Based / Append-only / Correction / Journal
  '/ledger/pools': {
    type: 'list',
    stats: [
      { label: '待对账', value: '--' },
      { label: '有差异', value: '--' },
      { label: '已平账', value: '--' },
      { label: '本月批次', value: '--' },
    ],
    searchPlaceholder: '搜索对账批次',
    filters: [
      {
        type: 'select', prop: 'ledger', label: '账本',
        options: [
          { label: 'APT 主账本', value: 'apt_main' },
          { label: '奖励账本', value: 'reward' },
          { label: '池子账本', value: 'pool' },
        ],
      },
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '待对账', value: 'pending' },
          { label: '有差异', value: 'diff' },
          { label: '已平账', value: 'balanced' },
        ],
      },
      range('对账时间'),
    ],
    columns: [
      { prop: 'batchNo', label: '对账批次', width: 170 },
      { prop: 'ledger', label: '账本', width: 140 },
      { prop: 'expected', label: '应收', width: 130, ...money },
      { prop: 'actual', label: '实收', width: 130, ...money },
      { prop: 'diff', label: '差异', width: 130, ...money },
      { prop: 'corrector', label: '冲正人', width: 110 },
      { prop: 'status', label: '状态', width: 100 },
      { prop: 'checkedAt', label: '对账时间', width: 170 },
    ],
  },
  // SoD：结算必须 Requester ≠ Approver
  '/ledger/corrections': {
    type: 'list',
    stats: [
      { label: '待审批', value: '--' },
      { label: '待结算', value: '--' },
      { label: '已完成', value: '--' },
      { label: '本月结算额', value: '--' },
    ],
    searchPlaceholder: '搜索结算单号',
    filters: [
      {
        type: 'select', prop: 'type', label: '类型',
        options: [
          { label: '竞猜结算', value: 'predict' },
          { label: '奖励结算', value: 'reward' },
          { label: '佣金结算', value: 'commission' },
        ],
      },
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '待审批', value: 'pending_approval' },
          { label: '待结算', value: 'pending' },
          { label: '处理中', value: 'processing' },
          { label: '已完成', value: 'done' },
          { label: '失败', value: 'failed' },
        ],
      },
      range('发起时间'),
    ],
    columns: [
      { prop: 'settleNo', label: '结算单号', width: 180 },
      { prop: 'type', label: '类型', width: 120 },
      { prop: 'amount', label: '金额', width: 130, ...money },
      { prop: 'requester', label: '发起人', width: 110 },
      { prop: 'approver', label: '审批人', width: 110 },
      { prop: 'status', label: '状态', width: 100 },
      { prop: 'createdAt', label: '发起时间', width: 170 },
    ],
  },
  // §11.7 Power：Available/Frozen/Consumed/Released/Recovering/Cap
  '/power/accounts': {
    type: 'list',
    stats: [
      { label: '总发行', value: '--' },
      { label: '已消耗', value: '--' },
      { label: '冻结中', value: '--' },
      { label: '回收中', value: '--' },
    ],
    searchPlaceholder: '搜索账户',
    filters: [
      {
        type: 'select', prop: 'state', label: '状态',
        options: [
          { label: '可用', value: 'available' },
          { label: '冻结', value: 'frozen' },
          { label: '回收中', value: 'recovering' },
        ],
      },
      range('更新时间'),
    ],
    columns: [
      { prop: 'uid', label: '账户', width: 140 },
      { prop: 'available', label: '可用', width: 120, ...money },
      { prop: 'frozen', label: '冻结', width: 120, ...money },
      { prop: 'consumed', label: '已消耗', width: 120, ...money },
      { prop: 'released', label: '已释放', width: 120, ...money },
      { prop: 'recovering', label: '回收中', width: 120, ...money },
      { prop: 'cap', label: 'Cap', width: 120, ...money },
      { prop: 'updatedAt', label: '更新时间', width: 170 },
    ],
  },

  // ================= 机器人管理（A-ROBOT） =================
  '/robot/list': {
    type: 'list',
    stats: [
      { label: '机器人总数', value: '--' },
      { label: '运行中', value: '--' },
      { label: '今日新增', value: '--' },
      { label: '累计奖励', value: '--' },
    ],
    searchPlaceholder: '搜索机器人 ID / 名称 / 用户',
    filters: [
      {
        type: 'select', prop: 'level', label: '等级',
        options: [
          { label: 'L1', value: 'L1' },
          { label: 'L2', value: 'L2' },
          { label: 'L3', value: 'L3' },
          { label: 'L4', value: 'L4' },
        ],
      },
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '运行中', value: 'running' },
          { label: '已暂停', value: 'paused' },
          { label: '已停用', value: 'disabled' },
        ],
      },
      range('创建时间'),
    ],
    columns: [
      { prop: 'robotId', label: '机器人 ID', width: 130 },
      { prop: 'name', label: '名称', width: 150 },
      { prop: 'owner', label: '所属用户', width: 120 },
      { prop: 'level', label: '等级', width: 90 },
      { prop: 'powerCap', label: 'Power Cap', width: 120, ...money },
      { prop: 'totalReward', label: '累计奖励', width: 120, ...money },
      { prop: 'status', label: '状态', width: 100 },
      { prop: 'createdAt', label: '创建时间', width: 170 },
    ],
  },
  '/robot/rewards': {
    type: 'list',
    stats: [
      { label: '今日收益', value: '--' },
      { label: '本月收益', value: '--' },
      { label: '累计收益', value: '--' },
      { label: '待领取', value: '--' },
    ],
    searchPlaceholder: '搜索机器人 / 收益类型',
    filters: [
      {
        type: 'select', prop: 'type', label: '类型',
        options: [
          { label: '挖矿奖励', value: 'mining' },
          { label: '升级奖励', value: 'upgrade' },
          { label: '推荐奖励', value: 'referral' },
        ],
      },
      {
        type: 'select', prop: 'status', label: '领取状态',
        options: [
          { label: '待领取', value: 'pending' },
          { label: '已领取', value: 'claimed' },
          { label: '已失效', value: 'expired' },
        ],
      },
      range('发生时间'),
    ],
    columns: [
      { prop: 'robotId', label: '机器人 ID', width: 130 },
      { prop: 'owner', label: '所属用户', width: 120 },
      { prop: 'type', label: '类型', width: 120 },
      { prop: 'amount', label: '金额', width: 130, ...money },
      { prop: 'status', label: '领取状态', width: 100 },
      { prop: 'createdAt', label: '发生时间', width: 170 },
    ],
  },

  // ================= 交易管理（A-OTC） =================
  // §11.6 CONTROLLED_MATCHING
  '/otc/orders': {
    type: 'list',
    stats: [
      { label: '今日订单', value: '--' },
      { label: '撮合中', value: '--' },
      { label: '已完成', value: '--' },
      { label: '争议订单', value: '--' },
    ],
    searchPlaceholder: '搜索订单号 / 用户',
    filters: [
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '待撮合', value: 'pending' },
          { label: '撮合中', value: 'matching' },
          { label: '已完成', value: 'done' },
          { label: '已取消', value: 'cancelled' },
        ],
      },
      {
        type: 'select', prop: 'matchType', label: '撮合方式',
        options: [
          { label: '自动撮合', value: 'auto' },
          { label: '手动撮合', value: 'manual' },
        ],
      },
      range('下单时间'),
    ],
    columns: [
      { prop: 'orderNo', label: '订单号', width: 180 },
      { prop: 'buyer', label: '买方', width: 120 },
      { prop: 'seller', label: '卖方', width: 120 },
      { prop: 'amount', label: '数量', width: 120, ...money },
      { prop: 'price', label: '单价', width: 120, ...money },
      { prop: 'total', label: '总额', width: 130, ...money },
      { prop: 'matchType', label: '撮合方式', width: 110 },
      { prop: 'status', label: '状态', width: 100 },
      { prop: 'createdAt', label: '下单时间', width: 170 },
    ],
  },
  '/otc/order-detail': {
    type: 'list',
    stats: [
      { label: '待处理', value: '--' },
      { label: '处理中', value: '--' },
      { label: '已解决', value: '--' },
      { label: '今日新增', value: '--' },
    ],
    searchPlaceholder: '搜索争议单号 / 订单号',
    filters: [
      {
        type: 'select', prop: 'type', label: '类型',
        options: [
          { label: '未收到资产', value: 'not_received' },
          { label: '金额不符', value: 'amount_mismatch' },
          { label: '重复支付', value: 'duplicate' },
          { label: '其他', value: 'other' },
        ],
      },
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '待处理', value: 'pending' },
          { label: '处理中', value: 'processing' },
          { label: '已解决', value: 'resolved' },
          { label: '已关闭', value: 'closed' },
        ],
      },
      range('发起时间'),
    ],
    columns: [
      { prop: 'disputeNo', label: '争议单号', width: 180 },
      { prop: 'orderNo', label: '关联订单', width: 180 },
      { prop: 'type', label: '类型', width: 120 },
      { prop: 'evidence', label: '证据', width: 160 },
      { prop: 'handler', label: '处理人', width: 110 },
      { prop: 'status', label: '状态', width: 100 },
      { prop: 'createdAt', label: '发起时间', width: 170 },
    ],
  },

  // ================= 赛事竞猜（A-PREDICT） =================
  '/prediction/markets': {
    type: 'list',
    stats: [
      { label: '今日赛事', value: '--' },
      { label: '进行中', value: '--' },
      { label: '待开赛', value: '--' },
      { label: '已结束', value: '--' },
    ],
    searchPlaceholder: '搜索赛事 / 联赛 / 球队',
    filters: [
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '待开赛', value: 'scheduled' },
          { label: '进行中', value: 'live' },
          { label: '已结束', value: 'finished' },
          { label: '已取消', value: 'cancelled' },
        ],
      },
      range('开赛时间'),
    ],
    columns: [
      { prop: 'matchId', label: '赛事 ID', width: 120 },
      { prop: 'league', label: '联赛', width: 170 },
      { prop: 'home', label: '主队', width: 140 },
      { prop: 'away', label: '客队', width: 140 },
      { prop: 'kickoff', label: '开赛时间', width: 170 },
      { prop: 'result', label: '赛果', width: 100 },
      { prop: 'marketCount', label: '市场数', width: 90, ...money },
      { prop: 'status', label: '状态', width: 100 },
    ],
  },
  '/prediction/results': {
    type: 'list',
    stats: [
      { label: '今日订单', value: '--' },
      { label: '待结算', value: '--' },
      { label: '已结算', value: '--' },
      { label: '总投注额', value: '--' },
    ],
    searchPlaceholder: '搜索订单号 / UID / 赛事',
    filters: [
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '待结算', value: 'pending' },
          { label: '已结算', value: 'settled' },
          { label: '已退款', value: 'refunded' },
          { label: '已取消', value: 'cancelled' },
        ],
      },
      range('下单时间'),
    ],
    columns: [
      { prop: 'orderNo', label: '订单号', width: 180 },
      { prop: 'uid', label: '用户', width: 120 },
      { prop: 'matchId', label: '赛事', width: 120 },
      { prop: 'selection', label: '选项', width: 120 },
      { prop: 'odds', label: '赔率', width: 100, ...money },
      { prop: 'amount', label: '金额', width: 130, ...money },
      { prop: 'status', label: '状态', width: 100 },
      { prop: 'createdAt', label: '下单时间', width: 170 },
    ],
  },
  // A-PREDICT-004 结果/结算/退款/更正（SoD）
  '/prediction/refunds': {
    type: 'list',
    stats: [
      { label: '待结算', value: '--' },
      { label: '待审批', value: '--' },
      { label: '已完成', value: '--' },
      { label: '退款中', value: '--' },
    ],
    searchPlaceholder: '搜索结算单号 / 赛事',
    filters: [
      {
        type: 'select', prop: 'type', label: '类型',
        options: [
          { label: '结算', value: 'settlement' },
          { label: '退款', value: 'refund' },
          { label: '更正', value: 'correction' },
        ],
      },
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '待审批', value: 'pending_approval' },
          { label: '处理中', value: 'processing' },
          { label: '已完成', value: 'done' },
          { label: '结果未知', value: 'unknown' },
        ],
      },
      range('结算时间'),
    ],
    columns: [
      { prop: 'settleNo', label: '结算单号', width: 180 },
      { prop: 'matchId', label: '赛事', width: 120 },
      { prop: 'result', label: '赛果', width: 100 },
      { prop: 'type', label: '类型', width: 110 },
      { prop: 'amount', label: '结算金额', width: 130, ...money },
      { prop: 'requester', label: '发起人', width: 110 },
      { prop: 'approver', label: '审批人', width: 110 },
      { prop: 'status', label: '状态', width: 100 },
      { prop: 'createdAt', label: '时间', width: 170 },
    ],
  },

  // ================= 数据中心（A-DATA） =================
  '/report/list': {
    type: 'dashboard',
    stats: [
      { label: '数据源接入', value: '--' },
      { label: '今日赛事数', value: '--' },
      { label: '信号准确率', value: '--' },
      { label: '数据同步延迟', value: '--' },
      { label: '异常数据源', value: '--' },
    ],
    searchPlaceholder: '搜索指标',
    filters: [range()],
    columns: [
      { prop: 'metric', label: '指标', width: 160 },
      { prop: 'value', label: '数值', width: 160, ...money },
      { prop: 'trend', label: '趋势', width: 140 },
      { prop: 'updatedAt', label: '更新时间', width: 170 },
    ],
  },
  // A-DATA-003 足球数据（已接入 GET /admin/arbitrage/fixture，字段对齐 arbitrage_fixture）
  '/data/football': {
    type: 'list',
    stats: [
      { label: '今日赛事', value: '--' },
      { label: '直播中', value: '--' },
      { label: '已完赛', value: '--' },
      { label: '数据源覆盖', value: '--' },
    ],
    searchPlaceholder: '搜索联赛 / 主队 / 客队',
    filters: [
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '待开赛', value: 'scheduled' },
          { label: '进行中', value: 'live' },
          { label: '已完赛', value: 'finished' },
        ],
      },
      range('开赛时间'),
    ],
    columns: [
      { prop: 'league', label: '联赛', minWidth: 160 },
      { prop: 'home', label: '主队', width: 140 },
      { prop: 'away', label: '客队', width: 140 },
      { prop: 'score', label: '比分', width: 100 },
      { prop: 'statusText', label: '状态', width: 100 },
      { prop: 'sourceText', label: '数据源', width: 130 },
      { prop: 'kickoffText', label: '开赛时间', width: 170 },
    ],
  },
  '/data/market': {
    type: 'list',
    stats: [
      { label: '市场总数', value: '--' },
      { label: '今日更新', value: '--' },
      { label: '赔率异常', value: '--' },
      { label: '数据源数', value: '--' },
    ],
    searchPlaceholder: '搜索赛事 / 市场',
    filters: [
      {
        type: 'select', prop: 'market', label: '市场',
        options: [
          { label: '1X2', value: '1x2' },
          { label: '让球', value: 'handicap' },
          { label: '大小球', value: 'over_under' },
        ],
      },
      {
        type: 'select', prop: 'source', label: '数据源',
        options: [
          { label: 'API-Football', value: 'api_football' },
          { label: 'BetBurger', value: 'betburger' },
        ],
      },
      range('更新时间'),
    ],
    columns: [
      { prop: 'matchId', label: '赛事', width: 120 },
      { prop: 'market', label: '市场', width: 150 },
      { prop: 'homeOdds', label: '主胜', width: 100, ...money },
      { prop: 'drawOdds', label: '平', width: 90, ...money },
      { prop: 'awayOdds', label: '客胜', width: 100, ...money },
      { prop: 'source', label: '数据源', width: 130 },
      { prop: 'updatedAt', label: '更新时间', width: 170 },
    ],
  },
  // A-DATA-005 套利信号（已接入 GET /admin/arbitrage/signal，字段对齐 arbitrage_signal）
  '/data/signal': {
    type: 'list',
    stats: [
      { label: '信号总数', value: '--' },
      { label: '高质量', value: '--' },
      { label: '低质量', value: '--' },
      { label: '今日新增', value: '--' },
    ],
    searchPlaceholder: '搜索赛事名称',
    filters: [
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '有效', value: 'valid' },
          { label: '已过期', value: 'expired' },
          { label: '已成交', value: 'used' },
          { label: '已关闭', value: 'closed' },
          { label: '无效', value: 'invalid' },
        ],
      },
    ],
    columns: [
      { prop: 'event_name', label: '赛事名称', minWidth: 180 },
      { prop: 'isLiveText', label: '滚球/赛前', width: 100 },
      { prop: 'profitRateText', label: '利润率', width: 100, ...money },
      { prop: 'leg1_bookmaker', label: 'Leg1 博彩公司', width: 130 },
      { prop: 'leg1_odds', label: 'Leg1 赔率', width: 90, ...money },
      { prop: 'leg2_bookmaker', label: 'Leg2 博彩公司', width: 130 },
      { prop: 'leg2_odds', label: 'Leg2 赔率', width: 90, ...money },
      { prop: 'current_score', label: '比分', width: 90 },
      { prop: 'startedAtText', label: '开赛时间', width: 170 },
      { prop: 'statusText', label: '状态', width: 90 },
    ],
  },
  // A-DATA-002 数据源管理（已接入 GET /admin/arbitrage/datasource，凭证可编辑/可测试）
  '/data/source': {
    type: 'list',
    stats: [
      { label: '数据源总数', value: '--' },
      { label: '健康', value: '--' },
      { label: '异常', value: '--' },
      { label: '今日同步量', value: '--' },
    ],
    searchPlaceholder: '搜索数据源名称',
    filters: [
      {
        type: 'select', prop: 'type', label: '类型',
        options: [
          { label: '足球数据', value: 'fixture' },
          { label: '套利信号', value: 'signal' },
        ],
      },
      {
        type: 'select', prop: 'status', label: '健康状态',
        options: [
          { label: '健康', value: 'healthy' },
          { label: '停用', value: 'disabled' },
          { label: '异常', value: 'error' },
        ],
      },
    ],
    columns: [
      { prop: 'name', label: '数据源名称', minWidth: 180 },
      { prop: 'typeText', label: '类型', width: 130 },
      { prop: 'configuredText', label: '配置状态', width: 110 },
      { prop: 'statusText', label: '健康状态', width: 110 },
      { prop: 'sync_count', label: '同步条数', width: 110, ...money },
      { prop: 'lastSyncAtText', label: '最后同步', width: 170 },
    ],
  },

  // ================= 风控与配置（A-RISK/A-APPROVAL/A-CONFIG/A-POLICY） =================
  '/risk/cases': {
    type: 'list',
    stats: [
      { label: '待处理', value: '--' },
      { label: '处理中', value: '--' },
      { label: '已升级', value: '--' },
      { label: '已解决', value: '--' },
    ],
    searchPlaceholder: '搜索事件 / 对象',
    filters: [
      {
        type: 'select', prop: 'type', label: '类型',
        options: [
          { label: '异常交易', value: 'abnormal_trade' },
          { label: '批量注册', value: 'mass_register' },
          { label: '套利滥用', value: 'arbitrage_abuse' },
          { label: '其他', value: 'other' },
        ],
      },
      {
        type: 'select', prop: 'level', label: '级别',
        options: [
          { label: '严重', value: 'critical' },
          { label: '高', value: 'high' },
          { label: '中', value: 'medium' },
          { label: '低', value: 'low' },
        ],
      },
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '待处理', value: 'open' },
          { label: '处理中', value: 'in_progress' },
          { label: '待审批', value: 'pending_approval' },
          { label: '已升级', value: 'escalated' },
          { label: '已解决', value: 'resolved' },
          { label: '已关闭', value: 'closed' },
        ],
      },
      range('触发时间'),
    ],
    columns: [
      { prop: 'caseId', label: '事件 ID', width: 120 },
      { prop: 'type', label: '类型', width: 130 },
      { prop: 'level', label: '级别', width: 90 },
      { prop: 'target', label: '对象', width: 140 },
      { prop: 'evidence', label: '证据', width: 160 },
      { prop: 'assignee', label: '处理人', width: 110 },
      { prop: 'status', label: '状态', width: 110 },
      { prop: 'createdAt', label: '触发时间', width: 170 },
    ],
  },
  // SoD：批准必须 Requester ≠ Approver，Approved ≠ Executed
  '/approval/center': {
    type: 'list',
    stats: [
      { label: '待审批', value: '--' },
      { label: '已批准', value: '--' },
      { label: '已驳回', value: '--' },
      { label: '今日发起', value: '--' },
    ],
    searchPlaceholder: '搜索审批单号',
    filters: [
      {
        type: 'select', prop: 'type', label: '类型',
        options: [
          { label: '资产调整', value: 'asset_adjustment' },
          { label: '账本冲正', value: 'ledger_correction' },
          { label: '参数发布', value: 'param_release' },
          { label: '结算', value: 'settlement' },
          { label: '用户限制', value: 'user_restriction' },
        ],
      },
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '待审批', value: 'pending' },
          { label: '已批准', value: 'approved' },
          { label: '已驳回', value: 'rejected' },
          { label: '已执行', value: 'executed' },
        ],
      },
      range('发起时间'),
    ],
    columns: [
      { prop: 'approvalNo', label: '审批单号', width: 180 },
      { prop: 'type', label: '类型', width: 130 },
      { prop: 'requester', label: '发起人', width: 110 },
      { prop: 'approver', label: '审批人', width: 110 },
      { prop: 'status', label: '状态', width: 110 },
      { prop: 'createdAt', label: '发起时间', width: 170 },
      { prop: 'resolvedAt', label: '审批时间', width: 170 },
    ],
  },
  '/config/definitions': {
    type: 'list',
    stats: [
      { label: '参数总数', value: '--' },
      { label: '待发布', value: '--' },
      { label: '生效中', value: '--' },
      { label: '已归档', value: '--' },
    ],
    searchPlaceholder: '搜索参数名',
    filters: [
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '草稿', value: 'draft' },
          { label: '待审批', value: 'pending_approval' },
          { label: '已批准', value: 'approved' },
          { label: '已排期', value: 'scheduled' },
          { label: '生效中', value: 'active' },
          { label: '已暂停', value: 'paused' },
          { label: '已回滚', value: 'rolled_back' },
          { label: '已归档', value: 'archived' },
        ],
      },
      range('更新时间'),
    ],
    columns: [
      { prop: 'paramKey', label: '参数名', width: 180 },
      { prop: 'currentValue', label: '当前值', width: 150 },
      { prop: 'candidate', label: '候选值', width: 150 },
      { prop: 'version', label: '版本', width: 110 },
      { prop: 'editor', label: '编辑人', width: 110 },
      { prop: 'status', label: '状态', width: 110 },
      { prop: 'updatedAt', label: '更新时间', width: 170 },
    ],
  },
  '/policy/list': {
    type: 'list',
    stats: [
      { label: '策略总数', value: '--' },
      { label: '生效中', value: '--' },
      { label: '待生效', value: '--' },
      { label: '已停用', value: '--' },
    ],
    searchPlaceholder: '搜索策略名',
    filters: [
      {
        type: 'select', prop: 'type', label: '类型',
        options: [
          { label: '地区策略', value: 'region' },
          { label: 'KYC 策略', value: 'kyc' },
          { label: '保护策略', value: 'protection' },
        ],
      },
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '生效中', value: 'active' },
          { label: '待生效', value: 'scheduled' },
          { label: '已停用', value: 'disabled' },
        ],
      },
      range('更新时间'),
    ],
    columns: [
      { prop: 'policyId', label: '策略 ID', width: 120 },
      { prop: 'name', label: '策略名', width: 180 },
      { prop: 'scope', label: '适用范围', width: 130 },
      { prop: 'condition', label: '条件', width: 180 },
      { prop: 'action', label: '动作', width: 140 },
      { prop: 'status', label: '状态', width: 100 },
      { prop: 'updatedAt', label: '更新时间', width: 170 },
    ],
  },

  // ================= AI 运营（A-AI） =================
  '/ai/dashboard': {
    type: 'dashboard',
    stats: [
      { label: '运行中策略', value: '--' },
      { label: '今日建议数', value: '--' },
      { label: '平均模拟收益', value: '--' },
      { label: '待处理建议', value: '--' },
    ],
    searchPlaceholder: '搜索指标',
    filters: [range()],
    columns: [
      { prop: 'metric', label: '指标', width: 160 },
      { prop: 'value', label: '数值', width: 160, ...money },
      { prop: 'trend', label: '趋势', width: 140 },
      { prop: 'updatedAt', label: '更新时间', width: 170 },
    ],
  },
  // §11.5 AI_SIGNAL / AI_ANALYSIS / AI_RECOMMENDATION / AI_SIMULATION / HUMAN_IN_LOOP
  '/ai/suggestion': {
    type: 'list',
    stats: [
      { label: '今日建议', value: '--' },
      { label: '待处理', value: '--' },
      { label: '已采纳', value: '--' },
      { label: '已忽略', value: '--' },
    ],
    searchPlaceholder: '搜索建议',
    filters: [
      {
        type: 'select', prop: 'type', label: '类型',
        options: [
          { label: 'AI 信号', value: 'ai_signal' },
          { label: 'AI 分析', value: 'ai_analysis' },
          { label: 'AI 建议', value: 'ai_recommendation' },
          { label: 'AI 模拟', value: 'ai_simulation' },
        ],
      },
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '待处理', value: 'pending' },
          { label: '已采纳', value: 'adopted' },
          { label: '已忽略', value: 'ignored' },
          { label: '人工复核中', value: 'human_in_loop' },
        ],
      },
      range('生成时间'),
    ],
    columns: [
      { prop: 'suggestionId', label: '建议 ID', width: 120 },
      { prop: 'type', label: '类型', width: 130 },
      { prop: 'summary', label: '内容摘要' },
      { prop: 'target', label: '影响对象', width: 140 },
      { prop: 'evidence', label: '依据', width: 160 },
      { prop: 'status', label: '状态', width: 120 },
      { prop: 'createdAt', label: '生成时间', width: 170 },
    ],
  },
  '/ai/simulation': {
    type: 'list',
    stats: [
      { label: '运行中', value: '--' },
      { label: '已完成', value: '--' },
      { label: '平均收益', value: '--' },
      { label: '最高收益', value: '--' },
    ],
    searchPlaceholder: '搜索模拟 / 策略名',
    filters: [
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '运行中', value: 'running' },
          { label: '已完成', value: 'done' },
          { label: '失败', value: 'failed' },
        ],
      },
      range('创建时间'),
    ],
    columns: [
      { prop: 'simulationId', label: '模拟 ID', width: 120 },
      { prop: 'strategy', label: '策略名', width: 180 },
      { prop: 'period', label: '回测周期', width: 150 },
      { prop: 'return', label: '收益率', width: 110, ...money },
      { prop: 'winRate', label: '胜率', width: 100, ...money },
      { prop: 'status', label: '状态', width: 100 },
      { prop: 'createdAt', label: '创建时间', width: 170 },
    ],
  },

  // ================= 系统管理（A-AUDIT/A-OPS） =================
  // §11.8 Actor/Action/Object/BeforeAfter/Reason/Evidence
  '/audit/logs': {
    type: 'list',
    stats: [
      { label: '今日操作', value: '--' },
      { label: '敏感操作', value: '--' },
      { label: '异常操作', value: '--' },
      { label: '审计完整', value: '--' },
    ],
    searchPlaceholder: '搜索操作人 / 对象',
    filters: [
      {
        type: 'select', prop: 'action', label: '动作',
        options: [
          { label: '新增', value: 'create' },
          { label: '修改', value: 'update' },
          { label: '删除', value: 'delete' },
          { label: '审批', value: 'approve' },
          { label: '导出', value: 'export' },
        ],
      },
      range('操作时间'),
    ],
    columns: [
      { prop: 'actor', label: '操作人', width: 120 },
      { prop: 'action', label: '动作', width: 140 },
      { prop: 'object', label: '对象', width: 160 },
      { prop: 'before', label: '变更前', width: 160 },
      { prop: 'after', label: '变更后', width: 160 },
      { prop: 'reason', label: '原因', width: 160 },
      { prop: 'evidence', label: '证据', width: 160 },
      { prop: 'ip', label: 'IP', width: 130 },
      { prop: 'createdAt', label: '时间', width: 170 },
    ],
  },
  '/ops/async-tasks': {
    type: 'dashboard',
    stats: [
      { label: '服务状态', value: '正常' },
      { label: '异步任务队列', value: '--' },
      { label: '今日错误数', value: '--' },
      { label: 'Redis 连接', value: '正常' },
    ],
    searchPlaceholder: '搜索服务 / 任务',
    filters: [
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '正常', value: 'ok' },
          { label: '异常', value: 'error' },
          { label: '降级', value: 'degraded' },
        ],
      },
      range(),
    ],
    columns: [
      { prop: 'service', label: '服务', width: 180 },
      { prop: 'status', label: '状态', width: 120 },
      { prop: 'uptime', label: '运行时长', width: 140 },
      { prop: 'remark', label: '备注' },
    ],
  },

  // ================= S03-P03 补齐（8 个权威 Page ID） =================
  // §11.4 Ledger Based / Append-only：账户 + 流水（只读，流水不可改）
  '/ledger/accounts': {
    type: 'list',
    stats: [
      { label: '账户总数', value: '--' },
      { label: 'APT 总余额', value: '--' },
      { label: '今日流入', value: '--' },
      { label: '今日流出', value: '--' },
    ],
    searchPlaceholder: '搜索账户 / 账本类型',
    filters: [
      {
        type: 'select', prop: 'ledger', label: '账本类型',
        options: [
          { label: 'APT 主账本', value: 'apt_main' },
          { label: '奖励账本', value: 'reward' },
          { label: '池子账本', value: 'pool' },
          { label: 'Power 账本', value: 'power' },
        ],
      },
      range('流水时间'),
    ],
    columns: [
      { prop: 'uid', label: '账户', width: 140 },
      { prop: 'ledger', label: '账本类型', width: 130 },
      { prop: 'balance', label: '当前余额', width: 150, ...money },
      { prop: 'direction', label: '方向', width: 100 },
      { prop: 'amount', label: '发生额', width: 130, ...money },
      { prop: 'txRef', label: '关联单号', width: 180 },
      { prop: 'createdAt', label: '流水时间', width: 170 },
    ],
  },
  // A-ROBOT-002 Robot详情与运行监控
  '/robot/detail': {
    type: 'list',
    stats: [
      { label: '运行中', value: '--' },
      { label: '待升级', value: '--' },
      { label: '今日产出', value: '--' },
      { label: '异常', value: '--' },
    ],
    searchPlaceholder: '搜索机器人 / 运行状态',
    filters: [
      {
        type: 'select', prop: 'runState', label: '运行状态',
        options: [
          { label: '运行中', value: 'running' },
          { label: '空闲', value: 'idle' },
          { label: '异常', value: 'error' },
          { label: '已暂停', value: 'paused' },
        ],
      },
      range('更新时间'),
    ],
    columns: [
      { prop: 'robotId', label: '机器人 ID', width: 130 },
      { prop: 'level', label: '等级', width: 90 },
      { prop: 'runState', label: '运行状态', width: 110 },
      { prop: 'todayOutput', label: '今日产出', width: 120, ...money },
      { prop: 'cpu', label: '算力占用', width: 100 },
      { prop: 'nextUpgrade', label: '下次升级', width: 170 },
      { prop: 'updatedAt', label: '更新时间', width: 170 },
    ],
  },
  // A-PREDICT-002 竞猜详情
  '/prediction/market-detail': {
    type: 'list',
    stats: [
      { label: '总投注额', value: '--' },
      { label: '参与人数', value: '--' },
      { label: '主胜占比', value: '--' },
      { label: '当前赔率', value: '--' },
    ],
    searchPlaceholder: '搜索投注 / 用户',
    filters: [
      {
        type: 'select', prop: 'selection', label: '选项',
        options: [
          { label: '主胜', value: 'home' },
          { label: '平', value: 'draw' },
          { label: '客胜', value: 'away' },
        ],
      },
      range('投注时间'),
    ],
    columns: [
      { prop: 'uid', label: '用户', width: 120 },
      { prop: 'selection', label: '选项', width: 100 },
      { prop: 'odds', label: '赔率', width: 100, ...money },
      { prop: 'amount', label: '金额', width: 130, ...money },
      { prop: 'payout', label: '预计派彩', width: 130, ...money },
      { prop: 'status', label: '状态', width: 110 },
      { prop: 'createdAt', label: '投注时间', width: 170 },
    ],
  },
  // A-CONFIG-002 参数发布与快照（审批后发布，历史快照不可变）
  '/config/releases': {
    type: 'list',
    stats: [
      { label: '待发布', value: '--' },
      { label: '已排期', value: '--' },
      { label: '生效中', value: '--' },
      { label: '快照数', value: '--' },
    ],
    searchPlaceholder: '搜索参数名 / 发布单号',
    filters: [
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '待发布', value: 'pending' },
          { label: '已排期', value: 'scheduled' },
          { label: '已生效', value: 'active' },
          { label: '已回滚', value: 'rolled_back' },
        ],
      },
      range('发布时间'),
    ],
    columns: [
      { prop: 'releaseNo', label: '发布单号', width: 180 },
      { prop: 'paramKey', label: '参数名', width: 180 },
      { prop: 'fromValue', label: '原值', width: 140 },
      { prop: 'toValue', label: '新值', width: 140 },
      { prop: 'snapshot', label: '快照版本', width: 120 },
      { prop: 'approver', label: '审批人', width: 110 },
      { prop: 'status', label: '状态', width: 110 },
      { prop: 'releasedAt', label: '发布时间', width: 170 },
    ],
  },
  // A-SUPPORT-002 工单详情
  '/support/ticket-detail': {
    type: 'list',
    stats: [
      { label: '回复条数', value: '--' },
      { label: '处理耗时', value: '--' },
      { label: '首次响应', value: '--' },
      { label: '升级次数', value: '--' },
    ],
    searchPlaceholder: '搜索回复内容 / 处理人',
    filters: [
      {
        type: 'select', prop: 'from', label: '来源',
        options: [
          { label: '用户', value: 'user' },
          { label: '客服', value: 'agent' },
          { label: '系统', value: 'system' },
        ],
      },
      range('回复时间'),
    ],
    columns: [
      { prop: 'from', label: '来源', width: 100 },
      { prop: 'author', label: '发言人', width: 120 },
      { prop: 'content', label: '内容' },
      { prop: 'attachment', label: '附件', width: 160 },
      { prop: 'createdAt', label: '回复时间', width: 170 },
    ],
  },
  // A-EMERGENCY-001 紧急操作（仅 Super Admin，执行需二次确认 + 审计）
  '/emergency/control': {
    type: 'list',
    stats: [
      { label: '今日执行', value: '--' },
      { label: '待确认', value: '--' },
      { label: '失败', value: '--' },
      { label: '影响范围', value: '--' },
    ],
    searchPlaceholder: '搜索紧急操作',
    filters: [
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '待确认', value: 'pending' },
          { label: '执行中', value: 'running' },
          { label: '已执行', value: 'done' },
          { label: '失败', value: 'failed' },
        ],
      },
      range('执行时间'),
    ],
    columns: [
      { prop: 'opNo', label: '操作编号', width: 180 },
      { prop: 'type', label: '操作类型', width: 160 },
      { prop: 'scope', label: '影响范围', width: 160 },
      { prop: 'operator', label: '操作人', width: 110 },
      { prop: 'status', label: '状态', width: 110 },
      { prop: 'evidence', label: '审计证据', width: 160 },
      { prop: 'createdAt', label: '执行时间', width: 170 },
    ],
  },
  // A-USER-004 用户资产调整（P1_CONDITIONAL，仅 Preview，审批制）
  '/admission/asset-adjust': {
    type: 'list',
    stats: [
      { label: '待审批', value: '--' },
      { label: '已批准', value: '--' },
      { label: '已驳回', value: '--' },
      { label: '今日调整额', value: '--' },
    ],
    searchPlaceholder: '搜索调整单号 / UID',
    filters: [
      {
        type: 'select', prop: 'type', label: '调整类型',
        options: [
          { label: 'APT 增加', value: 'apt_add' },
          { label: 'APT 扣减', value: 'apt_sub' },
          { label: 'Power 增加', value: 'power_add' },
          { label: 'Power 扣减', value: 'power_sub' },
        ],
      },
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '待审批', value: 'pending' },
          { label: '已批准', value: 'approved' },
          { label: '已驳回', value: 'rejected' },
        ],
      },
      range('发起时间'),
    ],
    columns: [
      { prop: 'adjustNo', label: '调整单号', width: 180 },
      { prop: 'uid', label: '用户', width: 120 },
      { prop: 'type', label: '调整类型', width: 130 },
      { prop: 'amount', label: '调整额', width: 130, ...money },
      { prop: 'reason', label: '原因', width: 160 },
      { prop: 'requester', label: '发起人', width: 110 },
      { prop: 'approver', label: '审批人', width: 110 },
      { prop: 'status', label: '状态', width: 110 },
      { prop: 'createdAt', label: '发起时间', width: 170 },
    ],
  },
  // A-MIGRATION-001 APT Migration（FUTURE，只读占位）
  '/migration/apt': {
    type: 'list',
    stats: [
      { label: '待迁移', value: '--' },
      { label: '迁移中', value: '--' },
      { label: '已完成', value: '--' },
      { label: '失败', value: '--' },
    ],
    searchPlaceholder: '搜索迁移批次',
    filters: [
      {
        type: 'select', prop: 'status', label: '状态',
        options: [
          { label: '待迁移', value: 'pending' },
          { label: '迁移中', value: 'running' },
          { label: '已完成', value: 'done' },
          { label: '失败', value: 'failed' },
        ],
      },
      range('迁移时间'),
    ],
    columns: [
      { prop: 'batchNo', label: '迁移批次', width: 180 },
      { prop: 'from', label: '来源', width: 140 },
      { prop: 'to', label: '目标', width: 140 },
      { prop: 'count', label: '数量', width: 100, ...money },
      { prop: 'status', label: '状态', width: 110 },
      { prop: 'createdAt', label: '迁移时间', width: 170 },
    ],
  },
};
