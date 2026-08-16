# tests/Feature — 占位入口（S02-P01）

本目录为 Feature 测试入口。S02-P01 为 OpenAPI/环境/通用内核包，**不实现任何 P0 业务写流程**，故无 Feature 测试。

Feature 测试（业务流程级，含 Controller/Validator/Service 集成）自 S02-P02 起随业务写流程落地：

- `S02-P02`：Auth / KYC / User / Eligibility
- `S02-P03`：Ledger / AptAccount / Power 事务模板
- `S02-P04`：Robot / Reward / Upgrade
- `S02-P05`：Prediction P0
- `S02-P06`：OTC / Power
- `S02-P07`：Approval / Parameter / Risk / Support / Notice / Audit

本包仅在此建立目录与契约说明，不伪造空通过的 Feature 测试。
