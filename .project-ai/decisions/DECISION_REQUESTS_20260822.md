# Owner Decision Requests — 2026-08-22

> 生成：中控调度 Agent（全量审核后修改批次）
> 纪律依据：README Agent 纪律 §5（权限/角色映射/正式参数修改必须人工确认）、manifest V3.4 CHANGE_CONTROL=OWNER_APPROVAL_REQUIRED
> 每条含：背景 → 建议（推荐项在前）→ 不决策的影响。未决策不阻塞无依赖开发。

## DR-01 ADMIN_ROLE_MAPPING · sys_admin.role_id → canonical 13 角色

- 背景：`AdminGovernanceRoleService::ROLES` 已补齐为 13（F-02），超管=is_admin 持全集；非超管 ROLE_MAP 仍空（fail-closed）。05 合同禁止 Agent 自填映射。
- 建议：**批准由 Owner 提供映射表**（role_id→roles 数组），Agent 仅录入并补契约测试。
- 备选：维持仅超管模式至 S03 收口后再定。
- 影响：不决策则 Admin 写路径继续仅超管可用；AD-02/AD-03 前端 RBAC 无法真实落地。

## DR-02 .env 凭据轮换

- 背景：本地 .env 含 Gmail 应用密码、SIGN_PRIVATE_KEY=projectApi、DOC_AUTH_PASS=doc123456，APP_DEBUG=true（未入 git）。
- 建议：Owner 轮换全部凭据 + 关 APP_DEBUG；CI 增加 secret 扫描。
- 影响：不决策则本机泄露面持续存在；不影响仓库。

## DR-03 V1 钱包域 bcmath 化

- 背景：UserWalletService/WithdrawOrderService/RechargeOrderService/arbitrage 用 float 运算金额；V2 域已 bcmath 18 位。
- 建议：批准资金域统一迁移 bcmath+DECIMAL 字符串（纯技术等价改造，逐服务带回归测试）。
- 备选：V1 域冻结不动（若线上已停用）。
- 影响：不决策则遗留支付/提现路径保留精度风险。

## DR-04 V2 统一鉴权中间件

- 背景：V2 组路由 middleware 全 '[]'，依赖每方法手写 getTokenUser。
- 建议：批准 api_v2 组挂 Auth 中间件 + 公开端点白名单（register/login/otp 等），一次性管道变更+全量回归。
- 影响：不决策则新增端点存在漏挂鉴权的人因风险。

## DR-05 Admin 前端 RBAC 落地顺序

- 背景：守卫仅验 token；13 角色前端零落地；v-permission 业务页零使用。
- 建议：DR-01 冻结后 → types 定义 Role 枚举与权限码 → 守卫读 registry requireServerAuth → 未授权跳 403(带原因) → 业务页按钮接 isActionAllowed。
- 影响：不决策则 Admin 高危页可 URL 直达（当前已登录用户）。

## DR-06 登录加密与签名机制处置

- 背景：登录密码 AES-ECB+硬编码密钥（views/login/index.vue:139-148）；api/http.ts SIGN_PRIVATE_KEY='projectApi' 入 bundle；VerifySign 后端已注释停用（BE-08）。
- 建议：确认走 HTTPS 后移除前端加密层与签名常量（删除死机制）；若契约要求签名则改为服务端网关签发。
- 影响：不决策则密钥持续暴露在 bundle。

## DR-07 Flutter P00 签署口径收口（FL-01）

- 背景：FLUTTER_ENGINEERING_DECISION.md 标"待 Owner 裁决"，而 build.gradle.kts 注释已写 "Owner-approved" 并按推荐落地依赖——触碰 S04-P00「Agent 不得代签」红线（口径不一，非既成事实代签）。
- 建议：Owner 对 P00 工程决策正式签署（或推翻），同步改代码注释与文档口径一致；随后启动 NEXT-03。
- 影响：不决策则 NEXT-03 无法合规启动（README 明令先收口再开工）。

## DR-08 README 既列待决项

- COLLABORATION_MODEL（建议：保留五角色分离，Quality 不阻塞 Developer）
- REMOTE_MASTER_RECONCILIATION（远端 master 25 前置提交的合并链）
- FLUTTER_CI_ENV（允许 loopback 的测试环境 + macOS iOS runner）

---
已执行且无需决策的修改见 reviews/WORKSPACE-AUDIT-V1-20260822.md §五（均为 README/NEXT 预处方或纯技术项）。
