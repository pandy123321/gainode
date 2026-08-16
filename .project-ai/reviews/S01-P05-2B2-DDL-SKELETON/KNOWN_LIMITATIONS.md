# KNOWN_LIMITATIONS — S01-P05 · 2B-2 DDL + Model/DAO/Service 骨架

## 前置条件（PRECONDITIONS）

- S01-P04 的 3 个缺 enum 对象（NotificationDelivery/MfaEnrollment/RiskCase）已 Owner 裁决（2B2-ENUM-01..03），并补入 05 §4 V2.4（2026-08-16）。
- 2B-2 状态转移矩阵仍为 CANDIDATE（未 FROZEN）。故本包仅骨架 + fail-closed，不实现转移。

## 工具限制（TOOL LIMITATIONS）

- AI Code Review Assistant 的 `get_latest_commit` 存在 diff 截断（`max_diff_chars` 配置无效，内部硬编码上限）。本包以 Python 脚本手工生成完整 DIFF.txt（126457 字符，46 文件全量不截断），绕过该限制。
- ChatGPT Web bridge 偶发会话失效（"无法向 ChatGPT 写入一次性审核契约"），属浏览器/ChatGPT 会话问题，不影响本地复审包完整性。

## 未定义维度（UNDEFINED）

- 8 个状态机对象的转移矩阵仍 CANDIDATE，独立审核（State Machine gate）通过后置 FROZEN。
- 生产参数（Snowflake worker ID、幂等键生成规则、审计事件编号）TBC。
- `verification_status`（SettlementMethod）的完整枚举值域未在 05 明确冻结，本包以 varchar(32) 承载，待后续 Freeze 补充 enum。

## 边界声明（BOUNDARY）

- 本包不触碰 V1.x 旧代码。`library/service/auth/` 下的 MemberAuth/AdminAuth/AuthAbstract 为既有 V1.x 认证代码（被 .gitignore 忽略），本包未改动，仅新增 AuthSessionService/MfaEnrollmentService（类名不冲突）。
- append-only 三层防护只覆盖 ORM 正常路径（Model/Builder/DAO）；显式底层 Query Builder / DB facade / PDO raw SQL 属数据库直连层，需 DB 级硬约束时另走 Change Request（与 OtcTrade/AuditEvent 同一边界）。
