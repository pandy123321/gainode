# FRONTEND_BASELINE_INVENTORY.md

> S03-P00 · 前端源码只读盘点 + 迁移分类
> 来源角色：Developer（只读盘点，未修改任何 source 文件）
> 日期：2026-08-16 · 状态：DRAFT（target roots 待 Owner 冻结）

## 0. Baseline（独立 Git 仓库，HEAD + tree）

```text
gainode_h5    HEAD=5e4b88b0016d747938c65b8fc4a9bfc65d3e42ae  TREE=7a6c63581b58a77757b557e6c16143b3e75fe359
gainode_admin HEAD=e0d2224a1ef4210f1276f906dfe34af22befb172  TREE=caa4800e13b113e9331f55c9089aa89706525c2d
gainode_api   HEAD=55e266fa26bae5246af8b811a8428dee32b4eabc  TREE=0b20918fad0673961d36301e1672e86894ff4a27
```

三个目录各自含 `.git`，均为独立仓库。`gainode_api` 是旧后端 Node 实现，**不纳入前端迁移**，
仅在此登记存在，S03 前端只面向 `gainode_h5` 与 `gainode_admin`。

分类语义：

- `KEEP`：技术栈/结构可复用，迁移后保留。
- `REPLACE`：结构可参考但实现需重写以对齐 V2（OpenAPI/7 语言/六请求头/权限模型）。
- `REMOVE_LATER`：V2 阶段暂存、后续删除（如 mock）。
- `DO_NOT_COPY`：永久禁止复制（链上、旧业务反推、模板演示残留）。

## 1. gainode_h5（`name=quiz`，极早期骨架）

| 路径 | 现状 | 分类 |
|---|---|---|
| package.json | Vue3.5+Vite8+TS6；链上依赖 solana/ethers/tronweb | REPLACE（移除链上，加 Vant4/vue-i18n/Pinia/Axios/Vitest/Playwright） |
| src/components/ConnectWallet.vue | 链上钱包连接 | DO_NOT_COPY |
| src/components/{BottomNav,ConfirmDialog,CountryPicker,PageHeader,ToastContainer}.vue | 手写基础组件 | REPLACE（Vant4 替代，仅交互参考） |
| src/i18n/{index,en-US,zh-CN}.ts | 手写 2 语言 | REPLACE（vue-i18n 7 语言） |
| src/stores/{project,user}.ts | 手写，非 Pinia | REPLACE（Pinia+persistedstate） |
| src/api/{http,services}.ts | 无六请求头/RESULT_UNKNOWN | REPLACE |
| src/router/index.ts | 无 router meta pageId/auth/restricted/feature | REPLACE |
| src/views | 空 | NEW |
| src/utils、src/assets | 极小 | REPLACE（按 V2 tokens 重建） |

**结论**：H5 几乎无业务实现，仅 `http.ts` 请求封装思路可参考，迁移后基本全新构建。

## 2. gainode_admin（`name=layui-vue-admin` 模板 + 旧业务残留）

| 路径 | 现状 | 分类 |
|---|---|---|
| package.json | pnpm@8.14.0、Vue3.3+Vite4+TS4.5、layui-vue、pinia、axios、echarts | KEEP（pnpm 与 07 一致） |
| 依赖 crypto-js/md5/js-base64 | 前端加密 | REPLACE（后端签名，前端不落 key） |
| 依赖 @aws-sdk/client-s3 | 疑似硬编码直传 | REPLACE（presigned URL） |
| 依赖 mockjs + src/mockjs/* | mock 数据 | REMOVE_LATER |
| src/components/FormSchema、TableSchema、ImageUpload、ImportSchema 等 | layui-vue schema 驱动 | KEEP |
| src/store/{app,index,user}.ts | Pinia | KEEP |
| src/api/http.ts | 基础封装 | REPLACE（六请求头/RESULT_UNKNOWN） |
| src/api/module/*.ts（15 个域） | 旧 API 封装 | REPLACE（对齐 V2 OpenAPI） |
| src/api/module/arbitrage.ts | 套利 API | DO_NOT_COPY |
| src/lang/{en_US,ko,zh_CN}.ts | 3 语言 | REPLACE（7 语言） |
| src/views/system/{admin,dept,dictionary,menu,role,option,file,language} | 系统管理 | REPLACE（V2 权限模型，结构参考） |
| src/views/permissions/{admin,menu,role} | 权限管理 | REPLACE |
| src/views/login、kyc | 登录/KYC | REPLACE（V2 流程） |
| src/views/mining/*、signal/*、redEnvelope、team、assets/{Recharge,Withdraw} | 旧业务残留 | DO_NOT_COPY |
| src/views/Configuration/* | 旧配置（arbitrage/funds/Payment/storage/system） | DO_NOT_COPY（反推风险） |
| src/views/{form,table,component,workSpace,error,result}/* | layui-vue 模板演示页 | DO_NOT_COPY |
| src/views/workSpace/monitor/moudel/* | 数百 citys/province 地图 JSON + china.js | DO_NOT_COPY |

**结论**：Admin 可复用 layui-vue + Pinia + schema 组件体系；业务页面与模板演示均需按 V2
Page ID 注册表（ADMIN_P0/P1）重建，旧业务残留与模板页不得反推。

## 3. secret 扫描

全文 ripgrep：私钥（`-----BEGIN`）、`private key`、`secretAccessKey`、`AKIA*`、`sk-*`、
`mnemonic/助记词/私钥`、`api_key=`、硬编码 `password=`。**结果 0 匹配**。

残余风险：`crypto-js`/`md5`/`@aws-sdk/client-s3` 依赖提示历史上存在前端加密/直传，
V2 迁移须改后端签名 + presigned URL（S03-P01 步骤 8），前端禁止持有真实密钥。

## 4. 迁移模式与写入边界（固定值，非 Owner 决策）

```text
MIGRATION_MODE = INCREMENTAL
TARGET_ROOT_WRITE_POLICY = DEVELOPMENT_ONLY
```

## 5. 待 Owner 决策

`H5_TARGET_ROOT` 与 `ADMIN_TARGET_ROOT` 见 `FRONTEND_TARGET_ROOT_DECISION.md`（DRAFT，A/B 方案）。
未签前 S03-P01 起的 H5/Admin 实现保持 FAIL_CLOSED。
