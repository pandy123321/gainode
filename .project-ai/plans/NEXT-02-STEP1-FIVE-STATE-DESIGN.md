# NEXT-02 步骤① 设计稿：H5 页面数据五态标注体系

> 状态：DESIGN-DRAFT（待 NEXT-01 收口后实施；不改变任何冻结业务规则）
> 依据：03 合同 STAGE-03 验收项「每页声明数据状态」+ 审核发现 H5-02（五态缺失）
> 现状盘点：UI 五态组件 components/FiveStateContainer.vue 已有 126 处使用（loading/empty/error/restricted/default）；
> **数据来源五态（REAL_DATA/READ_ONLY/FAIL_CLOSED/SKELETON/DEFERRED）零基建**。

## 1. 数据模型

```ts
// src/pageStates.ts
export const PAGE_DATA_STATES = ['REAL_DATA','READ_ONLY','FAIL_CLOSED','SKELETON','DEFERRED'] as const
export type PageDataState = typeof PAGE_DATA_STATES[number]

// Page ID 对齐 03 合同 m-* 编号；note 说明原因（FAIL_CLOSED 必填）
export const PAGE_STATES: Record<string, { state: PageDataState; note?: string }> = {
  'm-home-001': { state: 'READ_ONLY', note: '行情只读' },
  'm-predict-002': { state: 'FAIL_CLOSED', note: '下单写路径未解冻' },
  // …44 页逐一登记
}
```

## 2. 组件与可见性规则

- 新增 `components/DataStateBadge.vue`：state≠REAL_DATA 时在页面头部渲染徽标
  （READ_ONLY=蓝、SKELETON=灰、DEFERRED=橙、FAIL_CLOSED=红+note tooltip），REAL_DATA 不渲染。
- 接入方式：44 个 m-*/index.vue 在根节点加一行 `<DataStateBadge page-id="m-xxx-001" />`；
  FAIL_CLOSED 页面同时要求其写按钮已按 AssetAdjust.vue 模式禁用（双保险）。

## 3. 防漂移测试（vitest）

1. `page-states.spec.ts`：
   - 路由表中每个 component 的 Page ID 必须在 PAGE_STATES 注册（防漏页）；
   - PAGE_STATES 的 key 必须存在于路由表（防死键）；state 必须属于枚举；
   - FAIL_CLOSED 必须带非空 note。
2. 抽样挂载测试：随机取 5 个 FAIL_CLOSED 页断言 badge 渲染且文案含 note。

## 4. 初始登记基线（依据本轮审核结论）

| 基线 | 页面 |
|---|---|
| REAL_DATA | 登录/注册/MFA/KYC 提交/语言设置等已接真实端点页 |
| READ_ONLY | home 行情、notice 列表、asset 流水等只读聚合页 |
| FAIL_CLOSED | prediction 下单链、otc 下单链、robot 升级/领取链、withdraw/deposit 发起、migration |
| SKELETON | 结算中/竞猜结果等待类页面 |
| DEFERRED | 团队/邀请、免费预测互动等合同 DEFER 项 |

（逐页终表在实施时以路由表为准生成，本表为审核推断基线。）

## 5. 工作量与顺序

1. pageStates.ts + DataStateBadge.vue + 双向校验测试（半天）
2. 44 页接入 badge（机械改动，可并行分组）
3. vue-tsc + vitest 全绿 → STAGE-03 步骤①证据归档 .project-ai/reviews/

依赖：无 Owner 决策项（纯前端标注，不触碰业务规则）。
