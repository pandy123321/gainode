# S02-P02 · Auth / KYC / User / Eligibility — 验收

## 1. 机械断言（必须全过）

- `openapi/gainode-v2.yaml` 可解析；local `$ref` 无 dangling；operationId 全局唯一。
- 11 条权威接口 + 5 条只读契约路径均已定义，写操作声明 `Idempotency-Key` 与统一响应。
- PHP 语法：`php -l` 全部新/改文件通过；class-load/autoload 无错。
- 统一响应契约测试通过（success 8 元数据 + error 错误码映射）。
- `git diff --check` 无 whitespace 错误。
- secret scan：无新增明文 secret、密钥、生产 URL。

## 2. 六条子流程断言

1. register/login/otp/recovery/reset：幂等 + 频控；账号不存在与密码错误返回同一安全文案（不枚举）。
2. MFA：setup→confirm→challenge→revoke 状态合法；secret 不回显日志/响应。
3. Session：issue→refresh rotation→revoke 合法；refresh 重放与已撤销 token fail-closed。
4. KYC：submit→pending→review→(approve|reject|needs_info→resubmit) 合法；KYC_REVIEWER 不触碰资产。
5. FeatureEntitlement：global_p/AI/Prediction 三分支独立；TBC 默认 deny；allowed_actions 空数组。
6. LoginAudit + 安全 reason mapping：内部 code 不泄露，I18N key 映射，未知 code 回落 generic。

## 3. 非目标验证（不做）

- 不新增 DDL（AuthSession/MfaEnrollment/KycCase 表在 2B-2 已建，本包只写 Service 逻辑）。
- 不迁移 V1.x 佣金/套利/Web3/充值语义。
- 不写生产默认参数（OTP 供应商、KYC 地区/年龄、MFA 恢复政策 TBC）。
- 不实现经济写路径（留 S02-P03~P08）。

## 4. 验证命令（本包范围内）

```text
php -l <each new/modified file>
php tests/Contract/...        # envelope / error / openapi contract
php tests/Integration/...     # session rotation / mfa / kyc / cross-user
git diff --check
```

## 5. 交接声明（写入 REVIEW_REQUEST）

- `CONSUMED_UNFROZEN_CONTRACT = 2B-2 state transition matrix`（AuthSession/MfaEnrollment/KycCase 转移矩阵 CANDIDATE）。
- `OPEN_OWNER_DECISION`：OTP 供应商、KYC 地区/年龄门槛、MFA 恢复政策、FeatureEntitlement 06 参数。
- `NEXT_PACKAGE = S02-P03`。
