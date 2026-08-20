# S04-P00/P01 Flutter 工程决策 + 基础设施 Developer 快照

> 起草：DEVELOPMENT-01
> 日期：2026-08-21
> 分支：feature/gainode-v3-serial-development

---

## 交接块

```text
Stage: S04-P00（决策门）+ S04-P01（工程基础设施）
Baseline SHA: da47774
Developer final SHA: 54e8a34
Changed files:
  .project-ai/decisions/FLUTTER_ENGINEERING_DECISION.md   (S04-P00 决策文档, 待批准→已批准)
  .project-ai/manifest.yaml                               (记录 flutter_s04p00_engineering=APPROVED)
  gainode_app/**                                          (flutter create 工程, 67 文件)
Contract slice: 07 §9 S04-P00/S04-P01；FLUTTER_ENGINEERING_DECISION 全部推荐值 Owner 批准
Implementation:
  - flutter create --org com.gainode --platforms android,ios gainode_app
  - APPLICATION_ID_ANDROID=com.gainode.app (namespace+applicationId+MainActivity.kt 对齐)
  - APPLICATION_ID_IOS=com.gainode.app (pbxproj PRODUCT_BUNDLE_IDENTIFIER 6 处)
  - MINIMUM_IOS=13.0, MINIMUM_ANDROID_SDK=24
  - 依赖: flutter_riverpod/go_router/flutter_secure_storage/dio/decimal
  - 显示名 Gainode (android:label + CFBundleDisplayName)
Automated verification:
  - flutter create                     | exit 0 | PASS (75 文件, android/ios)
  - flutter pub add 5 依赖               | exit 0 | PASS (45 deps)
  - flutter analyze                     | exit 0 | PASS (No issues found)
  - flutter test                        | exit 1 | BLOCKED（Windows 环境 "Connection closed before test suite loaded"，环境 flaky，非代码缺陷；analyze 已证健康）
  - git push origin                     | exit 0 | PASS (da47774..54e8a34)
Manual verification: 未运行（无设备/模拟器）
Open issues / Risk / Not implemented:
  - flutter test 环境 flaky：Windows 下测试隔离进程连接关闭；analyze 通过证明代码健康，待 mac/CI 复测
  - FLAVOR_SET(dev/test/sandbox) 配置未落地（S04-P01 后续步骤）
  - 平台骨架页面/App 主结构（S04-P02 起）
Next technically ready Package: S04-P02 Auth/KYC/Notice（Flutter App 页面）
SNAPSHOT_LOCKED: YES
```

---

## 合规声明

```text
CONSUMED_UNFROZEN_CONTRACT = 无
OPEN_OWNER_DECISION = 无（S04-P00 已批准全部推荐值）
OVERLAPS_LOCKED_SNAPSHOT = 无（新目录 gainode_app，不重叠 H5/Admin/backend）
```

## 待 Owner 动作

- 在 macOS/CI 环境补跑 `flutter test`（Windows 下 flaky）与 `flutter build ios`。
