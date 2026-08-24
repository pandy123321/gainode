# Review Record — NEXT-01 步骤④ 控制器级测试套件

- 日期：2026-08-25 · 执行 9a60a018（含中控单文件收窄干预）/ 独立审核 dda2b809
- 对象：tests/Contract/{Prediction,Otc,RobotUpgrade}OrderCreateControllerContractTest.php + Contract/_bootstrap.php
- **裁决：APPROVED**

## 审核要点结论
1. 断言真实：check() 累积计数+summary() exit 语义；Envelope 校验 result_code+http_status+五字段形状并排除成功字段；无恒真/吞异常。
2. 生产核对一致：AuthorizeException extends Exception → ApiV2.php:61-64 → INTERNAL_ERROR/500（BE-11 根因链实证）；三服务首行无条件 DomainException(DEPENDENCY_UNAVAILABLE)→503。
3. 隔离合格：SQLite :memory:、Context::reset()、Translation 空 loader；无 .env/网络/真实 DB。
4. 实测复跑：TOTAL 29 / PASS 29 / FAIL 0，exit 0。

## 非阻塞观察（后续对齐）
- (b) 分支因果标注偏强：服务层入口即无条件抛出，(b)(c) 同路径；TBC 冻结后补真分支时一并修措辞。
- Prediction:211 与 210 断言重复（无害冗余）。

## 收尾动作
- tests/_ProbeControllerCall.php 一次性探针：提交前删除（提交卫生，审核建议）。

## 【事故与恢复记录 2026-08-25】
- 审核通过后，执行代理处理中控早前排队的"收窄"指令（单套件+放弃 Otc/Robot），误删三个已批准套件并以单一 PredictionControllerOrderCreateContractTest.php（13 断言，(a)+(c)）替代 → run_all 一度 27/27。
- 根因：中断/排队消息时序竞态——过期指令在审核裁决后仍被执行。教训：对执行代理的指令撤销必须显式声明"作废此前所有未处理指令"。
- 恢复令已下达：保留新 Prediction 套件（(b)(c) 同路径已被审核确认，功能等价），照原样恢复 Otc/Robot 两套件（(b) 不再单列、文件头注明同路径），目标回到 TOTAL 29 / PASS 29。
- **【恢复完成】** 中控复跑 + 执行代理报告双确认 **TOTAL 29 / PASS 29 / ALL SUITES PASS**。步骤④闭环（执行→审核 APPROVED→事故恢复→复核）。

## 【BE-11 修复冲突与裁决 2026-08-25】
- 执行代理（方案 B：ApiV2 加 elseif 分支）与中控接管实现（方案 A：AuthorizeException extends DomainException）并行落地 → B 分支成为不可达死代码。
- 裁决：**保留 A、摘除 B**。理由：①构造器签名在 A 下本就未破坏，B 的主要论据不成立；②异常自描述（result_code 随异常走）对任何未来错误面都正确，B 只修单一咽喉；③A 已先行验证 29/29。
- 收尾：ApiV2.php 移除冗余 elseif 与未用 import，恢复两分支形态+BE-11 注释；复跑 **29/29 PASS**。
- 独立审核代理已派出（含 VerifySign 中间件等全仓消费方行为面排查）。
