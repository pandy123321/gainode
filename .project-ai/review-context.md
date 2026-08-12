# Review Context — Compact Review Index / Critical Governance Summary

> **Purpose**: Compact review index for 60K context. Does NOT replace full source docs. Unlisted rules NOT invalidated.
> - Architecture Freeze → `.project-ai/architecture.md`
> - Review Rules → `.project-ai/rules/review.md`
> - V1 Baseline → `.project-ai/v1-baseline-review.md`（Lazy-load: V1.x migration/regression/legacy security）
> - Coding Rules → `.project-ai/rules/coding.md`（Execution Profile, Lazy-load: coding convention review）

**GLOBAL EVIDENCE ORDER = bootstrap.md §6 L1-L6**
L1 Runtime/DB → L2 Commit/Source/Machine Contract → L3 Owner/Independent Review → L4 Freeze Docs → L5 Self Review/Historical → L6 Agent Summary

---

## Diff Domain → Full Source Routing

Any code review MUST load the matching full source(s) below:

| Diff Domain | Required Full Source(s) |
|---|---|
| Any code review | `rules/review.md` — read chapters matching changed area |
| C-end commits | `rules/review.md` §前端审核清单 + §Vue 3 + TS + `architecture.md` |
| Trust Boundary / internal→external exposure | `architecture.md` |
| Admin commits | `rules/review.md` §前端审核清单 + §V2.4.1 治理文档审核 |
| Flutter (Dart) | `rules/review.md` §Flutter 审核清单 |
| Backend (PHP/API/Service) | `rules/review.md` §后端审核清单 + `architecture.md` |
| Ledger (APT/Power/append-only) | `rules/review.md` §账本安全 + `architecture.md` §11 |
| Authorization (RBAC/SoD/Approval) | `rules/review.md` §权限与安全 + `architecture.md` §11 |
| State machine (canonical states) | `rules/review.md` §状态机 + §状态契约 |
| Parameter/Approval | `rules/review.md` §资格与参数 + §状态契约 |
| Notification/Outbox | `rules/review.md` §通知与异步 |
| Cross-end consistency | `rules/review.md` §跨端一致性审核 |
| Contract Gap / Governance | `rules/review.md` §V2.4.1 治理文档审核 |
| Architecture/Module/Process/Path | `architecture.md` |
| Stage/Gate/Owner/Evidence | `bootstrap.md` |
| Coding convention | `rules/coding.md` |
| V1.x migration/regression/legacy | `v1-baseline-review.md` |
| Security credential/sensitive data | `rules/review.md` §安全审核 |

## Document Context Precedence (within enabledDocuments)

bootstrap.md > review-context.md > context.md > glossary.md
*Does NOT replace bootstrap.md §6 global L1-L6 evidence hierarchy.*

---

## Architecture Freeze (from architecture.md)

### Tech Stack
- Backend: PHP ≥8.2 + Webman, MySQL 8.4.9, illuminate/database, Redis 3 instances
- H5: Vue 3 + TS + Vant 4 + Pinia + vue-i18n
- Admin: Vue 3 + TS + Element Plus + Pinia
- App: Flutter (Dart 3+, null safety)
- Auth: JWT + Casbin RBAC

### Module Order (STAGE-01)
Auth/KYC → User/Eligibility → Robot/Reward → APT Ledger → Prediction → OTC/Power → Affiliate/Agent → AI Operations → Approval/Parameter → Support/Audit

### Key Paths
- Backend: `library/model/`, `library/dao/`, `library/service/` — all extend support\extend
- API routes via `sys_route` table; DDL: `sql/YYYYMMDD_description.sql`

### 13 Forbidden Actions (from architecture.md §11)
1. Frontend NO self-determined eligibility (must use allowed_actions/entitlement)
2. NO JS float for asset calc. 3. NO overwrite/delete ledger (reversal append-only)
4. NO bypass Approval for param changes. 5. NO rollback on notification failure
6. NO self-approve (non-emergency). 7. NO APR/APY/gambling vocab in UI
8. NO local defaults for TBC params. 9. NO delete sys_route records
10. Authorization MUST use full formula. 11. NO file_monitor in production
12. NO hardcoded signing/AES/S3 keys. 13. NO modify V1.x data before migration frozen

### Stage Boundaries
- STAGE-00: see bootstrap.md (IR-002 verdict: CONDITIONAL_APPROVAL, 1 P3 residual)
- STAGE-01: Backend Domain Objects only (Model/DAO/Service + DDL); FORBIDDEN: frontend code, V1.x internal logic changes, business logic, external API
- STAGE-02~05: Frontend → App → E2E → Production

---

## Critical Governance Summary (from rules/review.md)

### Review Principles
- Evidence-based; P0/P1 must be fixed and closed; explicit Verdict required (APPROVED / CHANGES_REQUIRED / APPROVED_WITH_CONDITIONS)
- Review only current diff

### Authority
- Single source: `Gainode_Development_Ready_V6.1_Latest/01–08`
- Conflict order: Product > Economics > Mobile > Admin > Data/Permissions/API > Parameters > Dev/Acceptance > Visual/I18N > i18n > Logo

### High-Risk Review Gates
- **Ledger**: APT/Power append-only, reversal via追加, idempotency verified
- **State Machine**: All domain states from 05 canonical enum; no self-invented states
- **Authorization**: Full formula (`canonical_role + data_scope + object_state + allowed_actions + risk_policy + SoD`). Non-emergency: SELF_APPROVAL = FORBIDDEN (different Actor required). Emergency (OWNER_DIRECTIVE 2026-08-12): ADMIN_SECURITY single person + MFA + 48h audit (case_id/reason/evidence).
- **Parameters**: TBC null/closed in production; ParameterRelease: saved≠effective, Approved≠Active
- **Notifications**: Outbox pattern; business state NOT rollback on notification failure

### Review Forbidden Actions
- Do not judge business rules from memory or guess
- Do not treat Demo/Mock data as production
- Do not treat TBC parameter values as formal defects
- Do not use old Figma/Flutter/Admin as review baseline
- Do not derive current requirements from history docs
- Do not require independent CI system before NO-GO
- Do not reject just because "it could be better"
