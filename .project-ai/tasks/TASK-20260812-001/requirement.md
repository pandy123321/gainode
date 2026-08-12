# TASK-20260812-001 · 开发启动前确认

> 日期：2026-08-12
> 状态：`AWAITING_APPROVAL`
> 阶段：Pre-development

## 需求

规范基线 V6.1 已就绪，开发启动前必须完成以下决策确认：

### 必确认项（阻塞开发）

1. **后端技术栈**：保留 PHP Workerman 还是迁移到其他（Node.js/Go/其他）
   - 现有代码：PHP/Webman + illuminate/database + MySQL 8.4.9（`0.5代码/gainode后端/gainode/`）
   - 关联：TASK-20260811-001 已基于 PHP/Webman 做了 10 模块后端开发计划（需求/设计/验收完整）
2. **前端框架**：保留 Vue 3 还是迁移到 React
   - 现有原型：Admin HTML 原型用 Vue 3 风格（`0.5代码/admin-proto/`）
   - 关联：TASK-20260811-001 已记录 Vue 3 决策
3. **数据库方案**：保留 MySQL 8.4 还是迁移到其他（PostgreSQL/SQLite）
   - 现有：MySQL 8.4.9，60 张表（`sql/database.sql`）
4. **区块链链**：Tron/BSC/Ethereum 三条链是否全保留
5. **AI Robot 引擎**：技术实现方案（外部 API / 自研模型 / 混合）
   - 现有：arbitrage 套利引擎 (BetBurger + API-Football)
   - 关联：TASK-20260811-001 已确认方案 B（改为内部 AI 经济引擎，不对 C 端暴露）
6. **遗留代码处理**：保留作为开发基础升级还是完全重写
   - 关联：TASK-20260811-001 已确认在现有代码基础上升级

### 非阻塞但强烈建议确认

6. 部署方案（传统服务器 / 云 / Serverless）
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
