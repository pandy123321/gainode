# BE-11 修复规格：未登录请求 401 契约对齐（NEXT-01 步骤⑤前置）

> 来源：控制器级测试实测发现（见 WORKSPACE-AUDIT-V1 BE-11）
> 现状：AuthorizeException → envelopeError → **500 INTERNAL_ERROR**
> 契约要求：05§7 → **401 + result_code=AUTH_UNAUTHENTICATED**

## 修复方案

1. 定位全局异常处理：`config/exception.php` 注册的 Handler 类（Webman 惯例 `app/exception/Handler.php`）。
2. 在异常映射中为 `AuthorizeException`（或实际承载鉴权失败的异常类，以 `library/` 内 throw 点为准 grep `AuthorizeException|AUTH_` 确认）增加专用分支：
   - HTTP status = 401
   - Envelope: `result_code='AUTH_UNAUTHENTICATED'`、`request_id` 透传、message 走 ErrorDict 文案
   - 附带 `WWW-Authenticate: Bearer` 响应头（RFC 6750 惯例，若现有 Envelope 层支持）
3. 不改变其他异常分支行为；DomainException→400 族与 POLICY_DENIED 分支保持原样。
4. 测试：
   - 控制器级套件中「未登录」分支断言从"当前 500 行为+差距注释"升级为断言 401/AUTH_UNAUTHENTICATED；
   - 已登录但 token 过期路径同样断言 401。
5. 回归：run_all 全绿；OpenAPI 中受影响端点响应码核对（多数已声明 401，无需改契约）。

## 边界

- 属错误管道技术对齐（契约已处方），不需 Owner 决策；但执行后须走独立审核闭环。
- 若发现 AuthorizeException 同时被权限不足（403 语义）复用，则拆分判定逻辑需先在套件中固化两类用例再动 handler。

## 执行时机

NEXT-01 步骤④测试落盘后、步骤⑤ HTTP 集成环境搭建时一并实施（同一批提交 C7/C9）。
