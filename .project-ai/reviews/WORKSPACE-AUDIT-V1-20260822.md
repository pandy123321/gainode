# Gainode 工作区全量代码审核报告 V1

> 日期：2026-08-22（Asia/Shanghai）
> 基线：feature/gainode-v3-serial-development @ 04b8e40
> 执行：中控调度 Agent（ox-alpha）+ 4 个独立只读审核子代理（后端/H5/Admin/Flutter）+ 中控自查
> 性质：Developer 侧工作底稿，供 Owner 复核；Gate 结论不因本报告自动变更。

## 一、范围与方法

- 深审（活跃代码）：`0.5代码/gainode后端/gainode`（PHP8.2+Webman）、`gainode_h5_v2`、`gainode_admin_v2`、`gainode_app`
- 免深审（README 规则：仅追溯参考）：`_existing_prod/**`、`历史文档/**`
- 方法：静态只读审查 + 合同比对（V6.1/01–08、H5/Admin 接口契约）+ README 断言逐条核验

## 二、README 断言核验（中控自查 + 后端代理）

| # | 断言 | 证据 | 结论 |
|---|---|---|---|
| 1 | ROLES 仅 11 角色 | AdminGovernanceRoleService.php:28-33 vs 05 §11.3 十三角色 | ✅ 属实（已修复，见 §五） |
| 2 | ROLE_MAP 为空 | 同上 :42 | ✅ 属实（保持 fail-closed，映射待 Owner） |
| 3 | 退款/冲正 fallback OPS_OPERATOR | AdminV2Controller.php:877,896 | ✅ 属实（已修复） |
| 4 | composer test 仅 1/26 脚本 | composer.json:66 | ✅ 属实（已修复） |
| 5 | KYC 等写路径 fail-closed | RefundCaseService.php:54 等 | ✅ 属实（KYC 限 admin 审核端；C 端 submit 已真实实现） |
| 6 | S02P09 测试为静态弱校验 | S02P09ControllerWiringContractTest.php 自述 | ✅ 属实 |
| 7 | Flutter main.dart=Counter Demo、P01 仅脚手架 | lib/ 仅 main.dart（逐字 Counter）；13/15 目标缺失 | ✅ 属实 |
| 8 | H5 147 测试 / Admin type+build PASS | vitest 实测复跑 147 通过；vue-tsc 复跑通过 | ✅ 属实 |

## 三、独立发现汇总

### 后端（BE）
| 编号 | 级别 | 摘要 | 状态 |
|---|---|---|---|
| BE-01 | P1 | 路由种子重复 key 吞 GET 路由（/orders、/ai/users/{id}/upgrade-orders 的 POST 行覆盖 GET 行） | ✅ 已修复（key 重生成，49/49 唯一，UTF-16LE 保留） |
| BE-02 | P1 | 本地 .env 含真实弱凭据（Gmail 应用密码/SIGN_PRIVATE_KEY=projectApi/DOC_AUTH_PASS/doc123456/APP_DEBUG=true）；未入库 | ⏸ Owner 决策 DR-02（轮换凭据） |
| BE-03 | P1 | 退款/冲正角色回退违反 SoD | ✅ 已修复 POLICY_DENIED |
| BE-04 | P2 | V1 遗留钱包域 float 运算（UserWalletService 等） | ⏸ Owner 决策 DR-03（资金语义） |
| BE-05 | P2 | raw() SQL 表达式拼接面 | ⏸ 随 BE-04 一并处理 |
| BE-06 | P2 | V2 无统一鉴权中间件（约定式 getTokenUser） | ⏸ Owner 决策 DR-04（管道变更） |
| BE-07 | P2 | 写端点零 validator（解冻前须补） | 📌 待办（解冻前置项） |
| BE-08 | P3 | VerifySign 全局注释停用 | ⏸ 随契约确认处理 |
| BE-09 | P3 | 无测试聚合入口/composer.lock 忽略/无 OpenAPI lint | ✅ 部分（runner+lint 完成；lock 提交建议随本批提交） |
| BE-10 | P3 | 种子↔OpenAPI 无对账 | ✅ 已建 lint 对账并修 6 缺口 |
| BE-11 | P1 | 【步骤④测试中实测发现】V2 未登录请求经 AuthorizeException→envelopeError 落为 **500 INTERNAL_ERROR**，违反 05§7 AUTH_UNAUTHENTICATED/401 映射；影响全部需登录端点的错误语义 | ✅ 已修复（F-10，方案 A 单机制；issue-20260825-0002 可关闭待审核裁决）；HTTP 层冒烟归 DR-08 CI |

### H5（H5）
| 编号 | 级别 | 摘要 | 状态 |
|---|---|---|---|
| H5-01/03/04/05 | P1/P2 | V1 死代码集群 ~34 文件（伪造领取成功数据、float 收益推导、XSS v-html、legacy 双轨 i18n） | ✅ 已整批删除（零引用逐一验证） |
| H5-02 | P1 | 五态标注体系缺失 | 📌 NEXT-02 步骤①待办 |
| H5-06 | P2 | m-kyc-002 consent_version 占位常量 | 📌 保持 PARTIAL fail-closed 至端点冻结 |
| H5-07 | P2 | FreshnessMeta 零消费 | 📌 NEXT-02 待办 |
| H5-08 | P3 | doRefresh 裸 axios 缺六请求头 | 📌 小修待办 |
| H5-10 | P3 | 工程内 README 为 "# Quiz" 脚手架残留 | 📌 待办 |
| — | P3 | i18n 缺 key `page.m_power_001.available`（7 语言 parity 实际缺口） | ✅ 已补齐 7 语言 |

### Admin（AD）
| 编号 | 级别 | 摘要 | 状态 |
|---|---|---|---|
| AD-01 | P0 | 生产包无条件引入 mockjs（RBAC 数据源为 mock） | ✅ 已修：DEV+VITE_ENABLE_MOCK 门控动态导入 + .env.example（独立审核 APPROVED） |
| AD-02 | P0 | 路由守卫不校验权限，requireServerAuth 未实现 | ⏸ 依赖角色映射冻结（DR-05）后实施 |
| AD-03 | P0 | canonical 13 角色前端零落地 | ⏸ 与 AD-02 同批（依赖 DR-05） |
| AD-04/05 | P1 | 12+ 处 MOCK_ONLY 假成功提示（含紧急控制"已执行"） | ✅ 两批合计 20 文件全部闭环：批一 13 文件（审核 APPROVED）+ 批二 7 文件 9 处（7 FAKE 已修/ListPage:335,354 验明 REAL 跳过；中控直接复核 grep 残留=2 均合理 + vue-tsc EXIT=0）。src/views 假成功清零 |
| AD-06 | P1 | 18 个真实 loader 被 mock 组件遮蔽 | 📌 NEXT-02 待办 |
| AD-07/08/09 | P1 | AES-ECB 硬编码密钥登录加密 / SIGN 私钥入 bundle / admin123456 后门 | AD-09 ✅ 已删 getLogin 后门（审核 APPROVED）；AD-07/08 ⏸ DR-06（登录/签名语义变更） |
| AD-11 | P2 | Admin 无测试基建 | 📌 NEXT-02 步骤③待办 |
| 其余 AD-10/12~20 | P2/P3 | console.log/any/i18n/死依赖等 | 📌 批量治理待办 |

### Flutter（FL）
| 编号 | 级别 | 摘要 | 状态 |
|---|---|---|---|
| FL-01 | P1 | FLUTTER_ENGINEERING_DECISION.md 标"待 Owner 裁决"但 build.gradle.kts 注释称"Owner-approved"，P00 签署口径不一 | ⏸ Owner 决策 DR-07 |
| FL-02 | P2 | release buildType 用 debug keystore（模板 TODO） | 📌 NEXT-03 内处理 |
| FL-03~06 | P3 | 镜像源记录/工具链 pin/linter 空/英文模板 README | 📌 NEXT-03 内处理 |

## 四、正面结论（可作 Gate 证据）

- 后端 fail-closed 纪律良好、ErrorDict 错误码字典化；26 套件全绿（复跑两次）。
- H5 API 层六请求头/single-flight/失败 POST 不自动重试/RESULT_UNKNOWN 处理完备且有测试；活跃路径金额零 float、资格全部服务端下发。
- H5 i18n 7 语言 465 key 全 parity 且有单测断言。

## 五、本轮已执行修改清单（全部有验证）

| # | 修改 | 文件 | 验证 |
|---|---|---|---|
| F-01 | 统一测试入口 tests/run_all.php + composer scripts(test/test:contract/test:integration/test:projection/test:ledger) | 后端 | 26/26 SUITE PASS ×2 |
| F-02 | ROLES 补齐 LEDGER_OPERATOR/AUDITOR → canonical 13（ROLE_MAP 未动） | AdminGovernanceRoleService.php | 契约测试新增 4 断言通过 |
| F-03 | refundCreate/correctionCreate 角色未匹配 → POLICY_DENIED（移除 ?? 'OPS_OPERATOR'） | AdminV2Controller.php | 套件绿 |
| F-04 | 路由种子重复 key 重生成（md5(api_v2|METHOD|URL)），编码 UTF-16LE 保留 | sql/20260820_v2_api_routes_seed.sql | 49/49 key 唯一 |
| F-05 | 新增 OpenAPI lint（L1 主文档双向覆盖/L2 operation 规范/L3 写幂等头/L4 $ref 解析/L5 种子↔OpenAPI 对账） | openapi/lint.php | PASS 0 error |
| F-06 | OpenAPI 补 6 个种子已注册但契约缺失的只读端点（robot_action_list/robot_upgrade_order_list/prediction_order_list/prediction_consent_receipt_list/otc_eligibility/parameter_active_release）+主文档注册 | openapi/** | lint PASS，operations 77→83 |
| F-07 | 删除 H5 V1 死代码集群 34 文件 + i18n legacy 双轨重写 | gainode_h5_v2/src/** | vitest 23 文件/147 用例 PASS；vue-tsc --build PASS |
| F-08 | Admin 合规批次：mockjs DEV 门控、admin/123456 后门删除、20 文件假提交→FAIL_CLOSED 禁用模式（两批） | gainode_admin_v2/** | 批一独立审核 APPROVED；批二中控直接复核（grep 假 success 清零、ListPage 2 处验明 REAL）；vue-tsc EXIT=0 ×3 轮 |
| F-09 | NEXT-01 步骤④：三套控制器级契约测试（Prediction/Otc/Robot orderCreate·upgradeOrderCreate），真实 Request+getTokenUser 全链，含未登录 Envelope、fail-closed 503、零落库负向断言 | tests/Contract/*OrderCreate*ContractTest.php ×3 | 独立审核 APPROVED（29/29 复跑）；事故恢复后双重复核 |
| F-10 | BE-11 修复：AuthorizeException 继承 DomainException(AUTH_UNAUTHENTICATED) → 未登录 401 对齐 05§7；三套件断言同步 401 | support/exception/AuthorizeException.php、support/controller/ApiV2.php、三套件 | run_all TOTAL 29 / PASS 29 / exit 0（修复前后各验证）；独立审核进行中 |

## 六、Owner Decision Request 队列（登记于 decisions/DECISION_REQUESTS_20260822.md）

DR-01 ADMIN_ROLE_MAPPING（role_id→13 角色映射填充）
DR-02 .env 凭据轮换与 APP_DEBUG 关闭
DR-03 V1 钱包域 bcmath 化（资金计算语义）
DR-04 V2 统一鉴权中间件（管道变更）
DR-05 Admin RBAC 前端落地顺序（依赖 DR-01）
DR-06 登录加密与签名机制处置（AES-ECB/VerifySign）
DR-07 Flutter P00 签署口径收口（FL-01）
DR-08 COLLABORATION_MODEL / REMOTE_MASTER_RECONCILIATION / FLUTTER_CI_ENV（README 既列）

## 七、后续开发队列（按 README NEXT 序）

NEXT-01 剩余：步骤①矩阵文档、步骤④真实控制器/服务测试、步骤⑤HTTP 集成环境、步骤⑧Gate 收口
→ NEXT-02：五态标注、Admin Vitest、页面接线、E2E/视觉/a11y（需浏览器环境）
→ NEXT-03：S04-P01 Flutter 基建（受 DR-07 制约的签署口径先收口）
→ NEXT-04/05/06 依次。
