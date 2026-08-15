# Gainode 开发执行计划冻结凭证 V3.1

```text
PROJECT = Gainode
WORKSPACE = E:\github\sports
FREEZE_ID = GAINODE-DEVELOPMENT-EXECUTION-PLAN-V3.1-20260816
FREEZE_STATUS = FROZEN_FOR_EXECUTION
FREEZE_DATE = 2026-08-16
TIMEZONE = Asia/Shanghai
OWNER_DIRECTIVE = APPROVED_FREEZE
SUPERSEDES = DEVELOPMENT_EXECUTION_PLAN_V3.0
OLDER_PLAN_STATUS = SUPERSEDED_DO_NOT_EXECUTE
```

## 1. 冻结对象

```text
FROZEN_PLAN_PATH = Gainode_Development_Ready_V6.1_Latest/07_DEVELOPMENT_AND_ACCEPTANCE.md
FROZEN_PLAN_VERSION = V3.1
FROZEN_PLAN_SHA256 = 2ED43F8AE320E3D8D643807B2E2EB01306953F5E9555D9E5AFA6CE3C7DA59838
FROZEN_PLAN_HASH_MODE = UTF8_LF_NO_BOM
EXECUTION_MODEL = ONE_DEVELOPMENT_AGENT_SERIAL_PACKAGES
QUALITY_MODEL = INDEPENDENT_READ_ONLY_SNAPSHOT_REVIEW
PLAN_CHANGE_CONTROL = OWNER_APPROVAL_REQUIRED
```

该 SHA-256 绑定本冻结凭证生成时的 V3.1 文件内容。计算前必须把文本解码为 UTF-8、把 `CRLF` 和单独的 `CR` 统一为 `LF`，再按 UTF-8 无 BOM 编码计算 SHA-256；这样 Windows 换行转换不会造成虚假失配。Development Agent、Quality Agent 和复审 Agent 启动时必须核对路径、版本、Freeze ID、状态和规范化 SHA-256。任一不匹配时不得使用旧计划补洞，也不得自行选择其他路线；应输出 `EXECUTION_PLAN_FREEZE_MISMATCH` 并请求 Owner 重新冻结。

## 2. 冻结范围

本次冻结以下执行规则：

- 唯一工作区 `E:\github\sports` 和 Gainode 项目身份。
- PHP 8.2 + Webman 2.1 + Workerman 后端基线；不迁移 Go。
- STAGE-01 至 STAGE-06 的目标、顺序和结束条件。
- 40 个工作包的 Package ID、先后关系、输入、允许范围、锁定范围、非目标、验证和停止条件。
- 单一 Development Agent 串行修改产品代码。
- Quality Agent 对每个 Package 锁定 commit/path/hash Snapshot 并独立审核。
- 每个 Formal Stage 的全部 Package 收口后单独执行 Stage Gate 审核。
- Snapshot 锁定且下一包路径不重叠、不消费未冻结合同时，Development Agent 可以继续，不需要等待 Quality 结论。
- Development、Quality、复审和生产授权结论必须分离。
- 已完成成果禁止重做；只有绑定当前提交的有效 Finding 可以触发最小修复。

## 3. 冻结工作包索引

```text
STAGE-01 = S01-P01,S01-P02,S01-P03,S01-P04,S01-P05,S01-P06,S01-P07,S01-P08,S01-P09
STAGE-02 = S02-P01,S02-P02,S02-P03,S02-P04,S02-P05,S02-P06,S02-P07,S02-P08,S02-P09
STAGE-03 = S03-P00,S03-P01,S03-P02,S03-P03,S03-P04
STAGE-04 = S04-P00,S04-P01,S04-P02,S04-P03,S04-P04,S04-P05,S04-P06,S04-P07
STAGE-05 = S05-P01,S05-P02,S05-P03,S05-P04,S05-P05
STAGE-06 = S06-P01,S06-P02,S06-P03,S06-P04
TOTAL_UNIQUE_PACKAGES = 40
```

Agent 不得新增、删除、合并、拆分、跳过或重排这些 Package。包内详细方案以冻结的 `07_DEVELOPMENT_AND_ACCEPTANCE.md` V3.1 为准，不得只依赖本索引实施。

## 4. 当前起点

```text
CURRENT_FORMAL_STAGE = STAGE-01_BACKEND_DOMAIN_OBJECTS
CURRENT_DEVELOPER_PACKAGE = S01-P01-MC2-REVIEW-LOCK
CURRENT_REVIEW_REVISION = 2795e38abd9bfff0383992f98ce01193e7fe1a5f
CURRENT_REVIEW_STATUS = RE_REVIEW_PENDING
MC1_FREEZE_STATUS = FROZEN
MC2_FREEZE_STATUS = NOT_FROZEN
PRODUCTION_APPROVAL = NO
```

后续实际进度可以由已验证的 Git 提交和独立审核结论向前推进，但不得回退或重做已经冻结、已完成的成果。

## 5. 质量审核绑定

Quality Agent 必须：

1. 先验证本冻结凭证和 V3.1 计划 SHA-256。
2. 按 V3.1 计划识别当前 Formal Stage 和 Package。
3. 每个 Package 单独锁定 `BASE_COMMIT..SNAPSHOT_COMMIT`、路径和哈希。
4. 每个 Package 输出独立质量报告、Finding、最小修复方案和复审结论。
5. 每个 Formal Stage 的全部 Package 通过后输出独立 Stage Gate 报告。
6. 不把 `DEV_NEXT_PACKAGE_ALLOWED`、`CODE_MERGE_RECOMMENDATION`、`FORMAL_STAGE_GATE` 和 `PRODUCTION_APPROVAL` 合并为同一结论。
7. 不因审核排队无条件阻塞路径不重叠且不消费未冻结合同的后续开发。

## 6. 变更控制

以下任一变更都必须先取得 Owner 明确批准：

- 改变 Formal Stage 或 Package 的数量、顺序、范围、依赖、验收或 Gate。
- 改变产品、经济、状态、API、DDL、权限、依赖、正式参数或生产边界。
- 把后端迁移到 Go 或其他语言。
- 把内部 AI 套利引擎暴露为 C 端产品。

批准后必须同时：

1. 升级 `07_DEVELOPMENT_AND_ACCEPTANCE.md` 版本。
2. 生成新的 Freeze ID 和冻结凭证。
3. 重新计算并记录 SHA-256。
4. 同步 `.project-ai/bootstrap.md`、`.project-ai/context.md` 和 `.project-ai/manifest.yaml`。
5. 由 Quality Agent 审核变更是否只影响获批范围。

在新冻结流程完成前，草案状态只能为 `DRAFT_NOT_EXECUTABLE`，不得用于开发或审核。
