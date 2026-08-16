# Acceptance: S03-P00 · 前端开发目录冻结

## 机械验收

| 项 | 方法 | 预期 | 实际 |
|---|---|---|---|
| baseline HEAD+tree | `git -C <repo> rev-parse HEAD "HEAD^{tree}"` | 3 仓库各 1 组 | ✅ H5/Admin/API 各已记录 |
| secret 扫描 | ripgrep 私钥/AWS/密码/助记词模式 | 0 匹配 | ✅ 0 |
| source 只读 | 盘点/复制期间未对 `_existing_prod/**` 做任何写/移动 | 无变更 | ✅ |
| 决策文件 | FRONTEND_TARGET_ROOT_DECISION.md | FROZEN（方案 A，OWNER_DIRECTIVE） | ✅ |
| 复制校验 | target 无 .git/node_modules/.env | 均 ABSENT | ✅ |

## 业务验收（07 §S03-P00 验收）

| 验收项 | 落地 | 状态 |
|---|---|---|
| 四路径/版本字段齐全 | H5/ADMIN SOURCE_ROOT + TARGET_ROOT + SOURCE_BASELINE_COMMIT/TREE 全填 | ✅ |
| source 仍只读 | 未修改 `_existing_prod/**`；复制到新 V2 目录 | ✅ |
| 迁移模式/允许写路径明确 | MIGRATION_MODE=INCREMENTAL + TARGET_ROOT_WRITE_POLICY=DEVELOPMENT_ONLY | ✅ |
| P01 无需自行选目录 | H5_TARGET_ROOT 已冻结，S03-P01 前置满足 | ✅ |

## baseline build/typecheck（步骤 6）

| 包 | 命令 | 结果 |
|---|---|---|
| H5 | npm run build | ✅ PASS（exit 0） |
| Admin | pnpm build:check | ❌ FAIL（exit 2，既有 layui-vue TableColumn TS2322/TS2345） |

## 停止条件核对

- 原停止条件「Owner 未冻结 target roots」已解除：Owner 2026-08-16「按照你的推荐执行」选定方案 A。
- 步骤 4–6（指针写入 + containment 校验 + 最小集复制 + baseline build）已按 07 §S03-P00 完成。

## 合同缺口 / Owner 待决（登记，不阻塞）

- **S03-P00-TARGET-ROOTS**：已 RESOLVED（方案 A 冻结）。
- 新登记（S03-P01 起处理，非 P00 阻塞）：H5 engines.node 偏差、Admin packageManager pnpm@8.14.0 偏差、
  Admin build:check 既有 TS 类型缺陷。

## 结论

S03-P00 完成只读盘点（H5 22 views 五域 + Admin layui-vue 模板 + 旧业务残留）、baseline 验证（3 仓库 HEAD+tree）、
secret 扫描（0 匹配）、KEEP/REPLACE/REMOVE_LATER/DO_NOT_COPY 分类，Owner 已签批方案 A 冻结 target roots，
最小构建集已复制（H5 72 files + Admin 539 files）并完成迁移前 baseline build（H5 PASS / Admin 既有 TS 缺陷 FAIL）。
S03-P01 前置「H5_TARGET_ROOT 已冻结」已满足。
