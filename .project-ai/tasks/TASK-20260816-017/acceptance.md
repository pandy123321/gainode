# Acceptance: S03-P00 · 前端开发目录冻结

## 机械验收

| 项 | 方法 | 预期 | 实际 |
|---|---|---|---|
| baseline HEAD+tree | `git -C <repo> rev-parse HEAD "HEAD^{tree}"` | 3 仓库各 1 组 | ✅ H5/Admin/API 各已记录 |
| secret 扫描 | ripgrep 私钥/AWS/密码/助记词模式 | 0 匹配 | ✅ 0 |
| source 只读 | 盘点期间未对 `_existing_prod/**` 做任何写/移动 | 无变更 | ✅ |
| 决策文件 | FRONTEND_TARGET_ROOT_DECISION.md | DRAFT，target 未冻结 | ✅ |

## 业务验收（07 §S03-P00 验收）

| 验收项 | 落地 | 状态 |
|---|---|---|
| 四路径/版本字段齐全 | H5/ADMIN SOURCE_ROOT 已填；TARGET_ROOT 待 Owner；SOURCE_BASELINE_COMMIT 已记录 tree | 部分（target 待 Owner） |
| source 仍只读 | 未修改 `_existing_prod/**` | ✅ |
| 迁移模式/允许写路径明确 | MIGRATION_MODE=INCREMENTAL + TARGET_ROOT_WRITE_POLICY=DEVELOPMENT_ONLY 已冻结为固定值 | ✅ |
| P01 无需自行选目录 | 一旦 Owner 冻结 TARGET_ROOT，S03-P01 前置满足 | 待 Owner |

## 停止条件核对

- Owner 未冻结 target roots → 本包停在步骤 3，只输出 Decision/风险，不复制不移动文件。✅ 符合。

## 合同缺口 / Owner 待决（登记，不阻塞）

- **S03-P00-TARGET-ROOTS**：H5_TARGET_ROOT / ADMIN_TARGET_ROOT 需 Owner 在 A/B 方案中选择后冻结
  （`NEEDS_OWNER_DECISION`）。未签前 S03-P01 起的 H5/Admin 实现保持 FAIL_CLOSED（前置 `H5_TARGET_ROOT` 已冻结不满足）。

## 结论

S03-P00 完成只读盘点（H5 极早期骨架 + Admin layui-vue 模板 + 旧业务残留）、baseline 验证（3 仓库 HEAD+tree）、
secret 扫描（0 匹配）、KEEP/REPLACE/REMOVE_LATER/DO_NOT_COPY 分类，并生成 Owner 路径决策请求（A/B 方案，DRAFT）。
因 Owner 未冻结 target roots，按停止条件不复制/移动文件，S03-P01 前置保持未满足。
