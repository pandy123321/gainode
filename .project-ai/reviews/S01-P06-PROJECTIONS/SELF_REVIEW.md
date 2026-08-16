# S01-P06 自审报告（Dev）

## 结论

S01-P06 已完成：7 个非持久投影（Response + ProjectionService + 测试）+ 2 公共基类，共 27 文件 / 1903 insertions。

## 完成清单

| 对象 | domain | Response | ProjectionService | Test |
|---|---|---|---|---|
| FeatureEntitlement | entitlement | ✅ | ✅ | ✅ |
| OtcEligibility | otc | ✅ | ✅ | ✅ |
| OtcCapacity | otc | ✅ | ✅ | ✅ |
| PowerImpactPreview | power | ✅ | ✅ | ✅ |
| SecurityProfile | auth | ✅ | ✅ | ✅ |
| SessionDevice | auth | ✅ | ✅ | ✅ |
| LoginAudit | auth | ✅ | ✅ | ✅ |

公共基类：`ProjectionResponse`（8 元数据字段 + toArray）、`ProjectionService`（default-deny + 元数据辅助 + rawUnix 容错）。

## 验证结果

```text
php -l                         = 24 文件全部 No syntax errors（16 生产 + 8 测试）
projection tests               = 105 断言 / 0 失败 / 0 Warning（7 测试）
git diff --cached --check      = PASS（无空白错误）
无新 DDL                        = 0 张生产表（_bootstrap 的 SQLite 建表是测试引导）
enum 一致性                     = reason_code 仅用 05 §3 冻结七选一
```

## 关键设计决策

1. **越权返回拒绝响应而非抛异常**：`AuthorizeException`/`VerifyException` 构造器会触发 `LangKeyService::saveTranslateValue`（写 DB），在纯单元测试（SQLite in-memory 无 sys_lang_key）下会失败。故越权统一返回 `data_status=UNAVAILABLE + refresh_hint='projection.access_denied'`，且不泄露存在性（存在/不存在不可区分）。

2. **data_status 语义**：`REALTIME`=投影返回了实时数据（即使部分 TBC 字段为 null）；`UNAVAILABLE`=投影无法返回任何有用数据（source 全不可用或越权）。`source_status`：`READY`=全 source 可读；`PARTIAL`=部分 TBC；`UNAVAILABLE`=无可读 source。

3. **Carbon 容错**：Model `$dateFormat='U'` 会把 created_time/updated_time 自动 cast 成 Carbon，`(int)` 强转报 warning。基类提供 `rawUnix()` 取 `getRawOriginal` 兼容 int/DateTimeInterface。

4. **LoginAudit 不读取未裁决 schema**：source-of-truth 未明确（Contract Gap G1），故不读 V1.x user_logs 也不读 audit_events，一律 UNAVAILABLE，避免依赖未裁决 schema。

## 已知取舍（详见 KNOWN_LIMITATIONS.md）

- OTC/Power/Feature/安全 规则参数全部 TBC → 默认 deny，无法做正向资格/容量/Power 计算。
- `allowed_actions` 字段在 05 §3 缺失（07 步骤 4 要求）→ 空数组（G2）。
- LoginAudit source 未裁决（G1）。
- OtcEligibility.capacity 结构未明确（G3）→ null。
- SessionDevice.revocable 撤销规则未冻结 → 默认 false（fail-closed）。
