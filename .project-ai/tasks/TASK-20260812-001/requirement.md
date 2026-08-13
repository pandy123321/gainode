# TASK-20260812-001 · 开发启动前确认

> 日期：2026-08-12
> 状态：`AWAITING_APPROVAL`
> 阶段：Pre-development

## 需求

规范基线 V6.1 已就绪。STAGE-00 Independent Review 两轮已完成：IR-001 CHANGES_REQUIRED → IR-002 修复后 CONDITIONAL_APPROVAL（15 项 Finding 全部闭合，0 P0 / 0 P1 / 0 blocking P2）。前置 Machine Contract 第一批（MC1）已通过独立审核（GAINODE-MC1-IR，记录 541/542/543）并经 Owner Signoff 置 FROZEN，STAGE-01 已授权启动（STAGE_STATUS = IN_PROGRESS）。

### 已完成确认项（全部 8 项 OWNER_DIRECTIVE 已关闭）

| # | 决策项 | 决定 | provenance |
|---|---|---|---|
| 1 | 后端技术栈 | PHP/Webman（在现有代码上增量升级） | OWNER_DIRECTIVE 2026-08-11 |
| 2 | 前端框架 | Vue 3 + TypeScript（H5 + Admin） | OWNER_DIRECTIVE 2026-08-11 |
| 3 | 数据库方案 | MySQL 8.4（保留现有） | OWNER_DIRECTIVE 2026-08-11 |
| 4 | AI Robot 引擎 | 方案 B：保留 arbitrage 为内部 AI 经济引擎，不对 C 端暴露 | OWNER_DIRECTIVE 2026-08-11 |
| 5 | 遗留代码处理 | 在现有代码基础上升级（非重写） | OWNER_DIRECTIVE 2026-08-11 |
| 6 | H5 组件库 | Vant 4 | OWNER_DIRECTIVE 2026-08-12 |
| 7 | Admin 组件库 | Element Plus（迁自 Layui Vue） | OWNER_DIRECTIVE 2026-08-12 |
| 8 | 数据迁移策略 | Big Bang 一刀切（双写技术不可行） | OWNER_DIRECTIVE 2026-08-12 |

### 仍然开放项（来自 IR-002 残留，不阻塞 STAGE-01）

1. **P3-001 (residual)**: H5 route count 口径对齐（不阻塞开发）
2. **Machine Contract 第二批**: OpenAPI 3.1 + Event Catalog + Environment Freeze — STAGE-01~02 并行

### 非阻塞但强烈建议确认

7. 部署方案（传统服务器 / 云 / Serverless）
7. CI/CD 流程
8. 测试框架选择
9. 代码审查流程与工具
10. Stage/Gate 治理流程

## 验收标准

- [x] `.project-ai` 上下文已创建（manifest + context + architecture + glossary + rules）
- [ ] 后端技术栈已确认并记录
- [ ] 前端框架已确认并记录
- [ ] 数据库方案已确认并记录
- [ ] AI Robot 引擎方案已确认
- [ ] 开发启动指令已发出

## 信息来源

- `Gainode_Development_Ready_V6.1_Latest/README.md`
- `01–08` 规范文档
- `0.5代码/` 遗留代码（参考）
