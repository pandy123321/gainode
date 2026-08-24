# Gainode H5（gainode_h5_v2）

Gainode AI 体育分析与预测平台的移动端 H5 前端（Vue 3 + Vite + TypeScript + Vitest）。

## 技术栈与结构

- Vue 3 + Vite + Pinia + vue-router + vue-i18n（7 语言：zh-CN / en-US / ja-JP / ko-KR / de-DE / th-TH / fr-FR）
- `src/api/`：统一 HTTP 客户端（六请求头：Authorization / Accept-Language / X-Request-Id / X-Timestamp；写操作自动注入 Idempotency-Key 与 If-Match）、Envelope 错误解析、refresh single-flight、失败 POST 不自动重试（UnknownResultError）
- `src/views/m-*`：44 个权威页面组件（Page ID 对齐 03 合同）；`src/stores/`：领域状态；`src/i18n/locales/*.json`：7 语言包
- 页面数据状态口径：REAL_DATA / READ_ONLY / FAIL_CLOSED / SKELETON / DEFERRED（STAGE-03 验收项，标注体系落地进行中）

## 常用命令

```bash
npm run test        # Vitest 单测（tests/unit，23 文件 / 147 用例）
npm run type-check  # vue-tsc --build
npm run build       # type-check + vite build
npm run test:e2e    # Playwright 冒烟（需浏览器）
```

## 红线（对齐 Gainode_Development_Ready_V6.1_Latest/03、05）

1. 金额一律字符串透传（禁 float 运算）；资格/收益由服务端下发，前端不推导。
2. 后端未冻结的写路径必须 FAIL_CLOSED（按钮禁用+原因），禁止 mock 成功。
3. RESULT_UNKNOWN(202) 视为未知态展示，不得当作成功或失败。
4. 7 语言 key parity 必须保持（有单测断言）。

> 权威文档：根目录 README.md → V6.1/01–08。本文件为工程说明，非业务规范。
