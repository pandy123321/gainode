# S01-P06 验证结果

## 已执行验证

| 验证项 | 命令/方法 | 结果 |
|---|---|---|
| PHP 语法 | `php -l`（24 文件） | PASS，全部 No syntax errors |
| 投影单元测试 | `php tests/projection/<7 对象>ProjectionTest.php` | PASS（105 断言 / 0 失败 / 0 Warning） |
| 空白检查 | `git diff --cached --check` | PASS |
| 无新 DDL | 人工核对 staged diff | PASS（0 张生产表；_bootstrap SQLite 建表为测试引导） |
| enum 一致性 | OtcEligibility reason_code 对齐 05 §3 七选一 | PASS（测试内 `[6]` 断言） |
| decimal string | available_before/frozen_before 保持 string | PASS（测试内断言 is_string） |
| 越权不泄露存在性 | 测试内 `[3]/[4]` 断言 | PASS |
| 无 mock fallback | 测试内 TBC 字段 null 断言 | PASS |

## 机械一致性断言

```text
NOT_PERSISTED = YES
DDL_TABLE_COUNT_DELTA = 0
PROJECTION_COUNT = 7
RESPONSE_COUNT = 7
PROJECTION_SERVICE_COUNT = 7
BASE_CLASS_COUNT = 2
METADATA_FIELDS = 8
NO_MOCK_FALLBACK = YES
DEFAULT_DENY = YES
DECIMAL_STRING = YES
CROSS_USER_ACCESS_FORBIDDEN = YES
CONTRACT_GAP_COUNT = 3
```

## 未执行验证（属后续包/阶段）

```text
真实 OTC/Power/Feature 资格计算 = NOT_RUN（参数未冻结）
业务写流程 = NOT_RUN（STAGE-02）
Controller/OpenAPI 路由 = NOT_RUN
参数冻结 = NOT_RUN（06 / ParameterRelease）
autoload/class-load 全量 = NOT_RUN（本次仅按需 require，未跑 composer dump-autoload）
```
