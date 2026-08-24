# 提交切分计划（收口用工作底稿）

> 分支：feature/gainode-v3-serial-development（README 处方工作分支，本地 commit 允许；push 需 Owner）
> 时点：2026-08-22 · 当前 72 项变更（含两执行代理在途改动，收口时以最终 git status 为准重核）

## Commit 切分（按可独立回滚的逻辑单元）

| # | Message（草案） | 内容 | 前置验证 |
|---|---|---|---|
| C1 | test(backend): unified runner + coverage matrix | tests/run_all.php、composer.json scripts、tests/TEST_MATRIX.md | composer test 全绿 |
| C2 | fix(backend): canonical 13 governance roles + POLICY_DENIED on unmapped admin role | AdminGovernanceRoleService.php、AdminV2Controller.php、AdminGovernanceRoleServiceContractTest.php | 同上套件绿 |
| C3 | fix(backend): dedupe api_v2 route seed keys (GET routes no longer swallowed) | sql/20260820_v2_api_routes_seed.sql（UTF-16LE 保持） | lint L5 + key 唯一性脚本 |
| C4 | feat(backend): OpenAPI lint + close 6 seed↔contract gaps | openapi/lint.php、gainode-v2.yaml、paths/{robot,prediction,apt_otc,policy_parameter}.yaml | php openapi/lint.php PASS |
| C5 | refactor(h5): remove V1 dead-code cluster (34 files) + single-track i18n | gainode_h5_v2/src/** 删除集、i18n/index.ts、locales/*.json、README.md、api/http.ts doRefresh | vitest 147/147 + vue-tsc |
| C6 | fix(admin): env-gate mockjs, remove backdoor login, fail-closed mock buttons | main.ts、mockjs/user.ts、~14 views、.env.example | vue-tsc --noEmit（代理交付后复核） |
| C7 | test(backend): controller-level suites for prediction/otc/robot create paths | tests/Contract/ 新增 3 套件 | run_all 29 SUITE PASS |
| C8 | docs(project): audit report V1 + decision requests + NEXT plans | .project-ai/**、PRODUCT_OVERVIEW.md（如属本轮） | — |

## 提交前统一检查清单

1. `git status` 重核：无临时文件（*.bak/.tmp-*）、无 node_modules/dist。
2. 后端 `composer test` 终跑全绿；H5 vitest+type-check 终跑全绿；Admin vue-tsc 终跑通过。
3. 种子文件编码校验：UTF-16LE BOM 完好（防止编辑器转码回归）。
4. 敏感扫描：新增/修改文件不含密钥、密码、token（BE-02 范畴仅登记不扩散）。
5. 每个 commit message 附证据行（套件数/用例数）。

## 明确不入库

- .env（保持 ignore）；任何 Decision Request 相关的映射填充（DR-01 未决）；
- Admin Vitest 基建与 H5 五态实施（NEXT-02，下一批次独立提交）。
