# Review Context — Compact Canonical Governance

> **Source-of-Truth**: 本文件是以下完整治理文档的无损摘要。
> 审核 Agent 必须在涉及下列领域时返回原文校验；本摘要不可替代权威原文。
> - Architecture Freeze → `.project-ai/architecture.md` (23.4 KB)
> - Review Rules → `.project-ai/rules/review.md` (7.8 KB)
> - V1 Baseline → `.project-ai/v1-baseline-review.md` (20.5 KB, Lazy-load for V1.x migration/regression)
> - Coding Rules → `.project-ai/rules/coding.md` (15.4 KB, Execution Profile)

---

## Architecture Freeze (from architecture.md)

### Tech Stack
- Backend: PHP ≥8.2 + Webman (Workerman), MySQL 8.4.9, illuminate/database ORM, Redis 3 instances
- Frontend H5: Vue 3 + TS + Vant 4 + Pinia + vue-i18n
- Frontend Admin: Vue 3 + TS + Element Plus + Pinia
- App: Flutter (Dart 3+, null safety)
- Auth: JWT (firebase/php-jwt) + Casbin RBAC
- CI: Three-stage (lint → php-cs-fixer + DDL → PHPUnit + i18n scan)

### Module Order (STAGE-01)
Auth/KYC → User/Eligibility → Robot/Reward → APT Ledger → Prediction → OTC/Power → Affiliate/Agent → AI Operations → Approval/Parameter → Support/Audit

### Key Path Constraints
- Backend: `library/model/{module}/`, `library/dao/{module}/`, `library/service/{module}/`
- All Service extends `support\extend\Service`, all Model extends `support\extend\Model`
- API routes via `sys_route` table (never edit `config/route/` directly)
- DDL: `sql/YYYYMMDD_description.sql`

### Trust Boundaries
- Arbitrage signals MUST NOT be exposed to C-end
- APT Ledger: append-only, reversal via追加分录 (never overwrite/delete)
- Authorization formula: `canonical_role + data_scope + object_state + allowed_actions + risk_policy + SoD`
- SoD: Actor-level invariant (`candidate.created_by_actor_id != approval.approved_by_actor_id`)

### 13 Forbidden Actions (from architecture.md §11)
1. Frontend: NO self-determination of eligibility (must read allowed_actions/entitlement)
2. Frontend: NO JS float for asset calculations
3. Backend: NO overwrite/delete ledger history (use reversal append-only)
4. Parameters: NO bypass of Approval workflow for changes
5. Business state: NO rollback on notification failure
6. Self-approval: SAME actor must not approve own request
7. UI copy: NO APR/APY/fixed-return/guaranteed/gambling vocabulary
8. TBC parameters: NO local default value fill for production
9. Routes: NO deletion of `sys_route` DB records
10. Authorization: MUST use full formula, not pure RBAC
11. Production: NO enable `file_monitor` process
12. Security: NO hardcoded API signing keys, AES keys, S3 credentials (V1.x legacy fix)
13. Data migration: NO direct modification of V1.x production data before migration plan frozen

### Stage Boundaries
- STAGE-00: Planning & Freeze (current: COMPLETE, verified by IR-002)
- STAGE-01: Backend Domain Objects only (Model/DAO/Service skeletons + DDL)
- STAGE-01 FORBIDDEN: frontend code, V1.x internal logic modifications, business logic details, external API integration
- Subsequent stages: STAGE-02 Frontend, STAGE-03 App, STAGE-04 E2E, STAGE-05 Production

---

## Review Governance (from rules/review.md)

### Review Principles
- Evidence-based; P0/P1 must be fixed and confirmed closed
- Every review must have explicit Verdict: APPROVED / CHANGES_REQUIRED / APPROVED_WITH_CONDITIONS
- Review only current diff, do not assume uncommitted code

### Authority
- Single requirement source: `Gainode_Development_Ready_V6.1_Latest/01–08`
- Conflict resolution: Product > Economics > Mobile > Admin > Data/Permissions/API > Parameters > Dev/Acceptance > Visual/I18N > i18n strings > Logo
- Never derive requirements from history docs / old Figma / old code

### High-Risk Review Gates (critical subset)
- **Ledger**: APT/Power ledger append-only, reversal via追加, idempotency verified
- **State Machine**: All domain states from 05 canonical enum; no self-invented states; `RESULT_UNKNOWN` only at request-resolution layer
- **Authorization**: Full formula required; SoD actor-level invariant; emergency ops require dual-auth + case_id + audit
- **Parameters**: TBC values stay null/closed in production; ParameterRelease: saved≠effective, Approved≠Active
- **Notifications**: Outbox pattern; business state must not rollback on notification failure
- **Cross-end consistency**: KYC/Robot/OTC/Prediction state sync across frontend+backend
- **Security**: No credential/secret disclosure; no account existence leak on login fail

### Review Forbidden Actions
- Do not judge business rules from memory or guess
- Do not treat Demo/Mock data as production
- Do not treat TBC parameter values as formal defects
- Do not use old Figma/Flutter/Admin as review baseline
- Do not derive current requirements from history docs
- Do not require independent CI system before NO-GO
- Do not reject just because "it could be better"

### Authoritative Evidence Order
bootstrap.md > architecture.md > rules/review.md > context.md > glossary.md > v1-baseline-review.md

The full documents remain authoritative. This compact context is for review budget control only.
When a review involves architecture boundaries, trust domain crossings, stage gate enforcement, or governance rule interpretation, the agent MUST reference the full source documents.
