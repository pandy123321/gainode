# FLUTTER ENGINEERING DECISION（S04-P00 强制架构 Decision Gate）

> 起草：DEVELOPMENT-01
> 日期：2026-08-21
> 状态：**APPROVED（Owner 2026-08-25 批准全部推荐值）**
> 依据：07 §9 S04-P00（STAGE-04 边界）；Owner 批示"全部按照建议执行"（含签署 DR-07）
> 规则：未经 Owner 批准不得运行 `flutter create`；Agent 不得代签生产签名/application ID。

---

## 前置核查（S04-P00 步骤 1）

```text
仓库内现有 Flutter 工程 = 无（无 pubspec.yaml）
重复 application ID     = 无（无 Flutter 工程）
可复用 assets            = Gainode_Development_Ready_V6.1_Latest/assets/logo/（8 个 logo 源：primary/symbol/app_icon/vertical/horizontal/mono/dark_splash）
                           + i18n 7 语言文案（与 H5 同源）+ Figma Gainode2.0 页面规范
Flutter 版本             = 3.47.0 stable（已安装）
Dart 版本                = 3.13.0（随 Flutter 3.47 派生）
目标路径                 = E:\github\sports\gainode_app（不存在，不覆盖 H5/Admin/backend/source mirror）
```

## 待 Owner 裁决字段（S04-P00 必须字段，Agent 不得代签）

```text
FLUTTER_TARGET_ROOT       = E:\github\sports\gainode_app        （推荐；不重叠现有目录）
APPLICATION_ID_IOS        = com.gainode.app                      （推荐；需 Owner 确认，不得与 H5/Admin 冲突）
APPLICATION_ID_ANDROID    = com.gainode.app                      （推荐；需 Owner 确认）
MINIMUM_IOS               = 13.0                                 （推荐；对齐 Flutter 3.47 默认下限）
MINIMUM_ANDROID_SDK       = 24                                   （推荐；Android 7.0）
EXACT_FLUTTER_VERSION     = 3.47.0                               （已装 stable，锁定）
DART_VERSION              = 3.13.0                               （由 Flutter 3.47 派生）
STATE_MANAGEMENT          = RIVERPOD | BLOC                      （需 Owner 选）
ROUTING                   = GO_ROUTER | APPROVED_EQUIVALENT      （需 Owner 选）
SECURE_STORAGE            = FLUTTER_SECURE_STORAGE | ...         （需 Owner 选）
HTTP_CLIENT               = DIO | APPROVED_EQUIVALENT            （需 Owner 选）
DECIMAL_LIBRARY           = decimal                                （推荐，保证金额精度，禁 double）
FLAVOR_SET                = dev,test,sandbox                     （推荐；固定三环境）
PRODUCTION_FLAVOR         = SCAFFOLD_ONLY_NO_REAL_VALUES         （推荐；仅脚手架，无真实值）
```

## 依赖候选（S04-P00 步骤 2：维护性/测试/deep link/安全/许可证）

| 类别 | OPTION_A | OPTION_B | 备注 |
|---|---|---|---|
| State Mgmt | RIVERPOD（推荐：强类型、可测试、无代码生成） | BLOC | Riverpod 社区活跃，测试友好 |
| Routing | GO_ROUTER（推荐：类型安全、deep link 支持） | 自定义 | GoRouter 是 Flutter 官方推荐 |
| Secure Storage | FLUTTER_SECURE_STORAGE（推荐：Keychain/Keystore） | hive（非安全） | 安全存储用系统钥匙串 |
| HTTP | DIO（推荐：拦截器、超时、刷新） | http | DIO 支持单例刷新/超时 |
| Decimal | decimal（推荐：String+decimal value object，禁 double） | 无 | 对齐 05 契约金额精度 |

## 安全/平台边界（S04-P00 步骤 4）

```text
安全存储 threat model     = Token/secret 经系统 Keychain/Keystore，日志/响应不回显
签名边界                 = 不生成真实证书/keystore；仅 dev/test/sandbox flavor scaffold
iOS 构建                = 必须在 macOS 构建（Windows 环境不产出 iOS 产物，仅生成工程）
CI runner              = 待 Owner 指定（macOS runner 用于 iOS）
生产签名值               = 不写入仓库（PRODUCTION_FLAVOR=SCAFFOLD_ONLY_NO_REAL_VALUES）
```

## 路径 Containment（S04-P00 步骤 3）

```text
target path = E:\github\sports\gainode_app
不覆盖       = H5(gainode_h5_v2) / Admin(gainode_admin_v2) / backend(0.5代码) / _existing_prod(source mirror)
允许写路径   = gainode_app/**（Flutter 工程全量）
平台目录     = gainode_app/android, gainode_app/ios, gainode_app/lib, gainode_app/test
```

## 验证/交付（S04-P00 验证项）

```text
路径 containment      = PASS（gainode_app 不重叠）
application ID 冲突   = 无（无现有 Flutter 工程）
Flutter/Dart 兼容     = Flutter 3.47.0 / Dart 3.13.0 匹配
依赖许可证/维护性      = 已列候选（Riverpod/GoRouter/secure_storage/DIO/decimal 均 MIT/BSD 友好）
安全存储 threat model = 已说明（Keychain/Keystore）
```

---

## Owner 批准记录

1. 裁决字段全部采用推荐值：APPLICATION_ID_IOS/ANDROID=com.gainode.app、MINIMUM_IOS=13.0、MINIMUM_ANDROID_SDK=24、STATE_MANAGEMENT=RIVERPOD、ROUTING=GO_ROUTER、SECURE_STORAGE=FLUTTER_SECURE_STORAGE、HTTP_CLIENT=DIO、DECIMAL_LIBRARY=decimal、FLAVOR_SET=dev,test,sandbox、PRODUCTION_FLAVOR=SCAFFOLD_ONLY_NO_REAL_VALUES。
2. **已批准**（Owner 2026-08-25，"全部按照建议执行"→ 签署 DR-07）。`android/app/build.gradle.kts` 中 "Owner-approved" 注释与本文档口径一致。
3. 批准后可启动 NEXT-03（S04-P01 基础设施）。
