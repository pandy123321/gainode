# Design: S03-P00 · 前端开发目录冻结

## 实施策略

本包是「只读盘点 + baseline 验证 + Owner 决策请求」，不产生产品代码。
Developer 只执行 07 §S03-P00 步骤 1–3，步骤 4–6 因 Owner 未冻结 target roots 而停（停止条件）。

## 1. 只读盘点结果（步骤 1）

### gainode_h5（`name=quiz`，极早期骨架）

| 维度 | 现状 | 分类 |
|---|---|---|
| 框架 | Vue 3.5 + Vite 8 + TS 6 | REPLACE（Vite/TS 版本对齐 V2 基线） |
| 链上依赖 | `@solana/web3.js`、`ethers`、`tronweb` | **DO_NOT_COPY**（§0.1 链上禁止） |
| 组件 | `ConnectWallet.vue`（链上钱包） | **DO_NOT_COPY** |
| 组件 | BottomNav/ConfirmDialog/CountryPicker/PageHeader/ToastContainer | REPLACE（Vant 4 替代，仅作交互参考） |
| i18n | 手写 index.ts + en-US/zh-CN（2 语言） | REPLACE（V2 7 语言 + vue-i18n） |
| stores | 手写 project.ts/user.ts（非 Pinia） | REPLACE（Pinia + persistedstate） |
| api | http.ts/services.ts（无六请求头/RESULT_UNKNOWN） | REPLACE |
| views（22 .vue） | Home/login/my/robot/team 五域（含 robot Signals/ArbitrageRecords/Agent） | REPLACE（V2 M-* 重建；robot 套利信号视图不得原样反推） |
| 结论 | H5 有 22 视图作结构/交互参考，但全部按 V2 契约重写；链上依赖移除 | |

### gainode_admin（`name=layui-vue-admin` 模板）

| 维度 | 现状 | 分类 |
|---|---|---|
| 框架 | Vue 3.3 + Vite 4 + TS 4.5 + pnpm@8.14.0 | KEEP（pnpm 与 07 一致） |
| UI | layui-vue + @layui/json-schema-form | **KEEP**（schema 驱动后台，可复用） |
| 状态 | pinia + pinia-plugin-persistedstate | **KEEP** |
| 请求 | axios | **KEEP** |
| 图表 | echarts | KEEP |
| 加密依赖 | `crypto-js`、`md5`、`js-base64` | REPLACE（后端签名，前端不落 key） |
| 上传 | `@aws-sdk/client-s3`（疑似硬编码 key） | REPLACE（presigned URL） |
| mock | `mockjs` + src/mockjs/* | **REMOVE_LATER**（V2 走真实 API） |
| 模板演示页 | form/table/component/workSpace/error/result | **DO_NOT_COPY**（模板残留） |
| 旧业务残留 | mining/signal/arbitrage/redEnvelope/team/assets(Recharge/Withdraw) | **DO_NOT_COPY**（§0.1 不反推旧业务） |
| 地图数据 | workSpace/monitor/moudel/*（数百 citys/province JSON） | **DO_NOT_COPY**（演示数据） |
| 系统管理 | system/{admin,dept,dictionary,menu,role,option,file,language} | REPLACE（V2 权限模型，仅结构参考） |
| api/module | 15 个域（含 arbitrage.ts） | REPLACE（对齐 V2 OpenAPI；arbitrage.ts DO_NOT_COPY） |
| lang | en_US/ko/zh_CN（3 语言） | REPLACE（V2 7 语言） |
| components | FormSchema/TableSchema/ImageUpload 等 schema 组件 | **KEEP**（layui-vue 驱动，可复用） |

## 2. baseline 验证（步骤 2）

三个 source 目录均为**独立 Git 仓库**（各自含 `.git`），baseline 用 git HEAD + tree hash：

```text
gainode_h5    HEAD=5e4b88b0016d747938c65b8fc4a9bfc65d3e42ae  TREE=7a6c63581b58a77757b557e6c16143b3e75fe359
gainode_admin HEAD=e0d2224a1ef4210f1276f906dfe34af22befb172  TREE=caa4800e13b113e9331f55c9089aa89706525c2d
gainode_api   HEAD=55e266fa26bae5246af8b811a8428dee32b4eabc  TREE=0b20918fad0673961d36301e1672e86894ff4a27
```

（`gainode_api` 为旧后端 Node 实现，S03-P00 不纳入前端目录决策，仅在清单登记其存在。）

## 3. secret 扫描结果

对 `_existing_prod` 全文扫描私钥/助记词/API key/AWS 凭证/硬编码 password，**无匹配**。
风险残留：`crypto-js`/`md5`/`@aws-sdk/client-s3` 依赖本身提示历史上存在前端加密/直传，
V2 迁移时必须改为后端签名 + presigned URL（S03-P01 步骤 8），禁止前端持有真实密钥。

## 4. Owner 决策请求（步骤 3）

生成 `.project-ai/decisions/FRONTEND_TARGET_ROOT_DECISION.md`（DRAFT），
给出 A/B 两方案（默认推荐 A），Agent 不签字，待 Owner 选择后写入四路径指针。

## 5. 文件清单

```text
.project-ai/tasks/TASK-20260816-017/{requirement,design,acceptance}.md
.project-ai/decisions/FRONTEND_TARGET_ROOT_DECISION.md   （DRAFT，A/B 方案）
.project-ai/decisions/FRONTEND_BASELINE_INVENTORY.md     （盘点 + KEEP/REPLACE/REMOVE_LATER/DO_NOT_COPY）
.project-ai/manifest.yaml / context.md                   （路径指针 + 进度）
```

## 6. 验证矩阵

| 项 | 方法 | 预期 |
|---|---|---|
| baseline 可追溯 | git rev-parse HEAD^{tree}（三仓库） | 3 个 HEAD + tree 已记录 |
| secret 扫描 | ripgrep 私钥/AWS/密码模式 | 0 匹配 |
| 不碰 source | `git status --short` 于 `_existing_prod` 各仓库 | 无变更（只读） |
| 决策未签 | FRONTEND_TARGET_ROOT_DECISION.md | DRAFT 状态，H5/ADMIN_TARGET_ROOT 未冻结 |
| 指针一致 | manifest/context | 登记 S03-P00 进行中 + target 待 Owner |
