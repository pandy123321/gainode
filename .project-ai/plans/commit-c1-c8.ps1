# Commit sequence C1-C8 (local commits only; NO push — Owner decides push)
# Usage: pwsh -File commit-c1-c8.ps1
$ErrorActionPreference = 'Stop'
Set-Location 'E:\github\sports'

function Commit($files, $msg) {
    git add -- @files
    git commit -m $msg
    if ($LASTEXITCODE -ne 0) { throw "commit failed: $msg" }
}

$B = '0.5代码/gainode后端/gainode'

Commit @(
    "$B/tests/run_all.php", "$B/composer.json", "$B/tests/TEST_MATRIX.md"
) "test(backend): unified test runner (26 suites) + coverage matrix

- tests/run_all.php: recursive *Test.php discovery, '_' prefix excluded,
  one subprocess per suite, exit 0/1, substring filter
- composer scripts: test / test:contract / test:integration / test:projection / test:ledger
- TEST_MATRIX.md: 05-contract domain -> suite mapping + gap plan
Evidence: composer test = 26/26 SUITE PASS (x2 at build time)"

Commit @(
    "$B/library/service/admin/AdminGovernanceRoleService.php",
    "$B/app/admin/controller/v2/AdminV2Controller.php",
    "$B/tests/Contract/AdminGovernanceRoleServiceContractTest.php"
) "fix(backend): canonical 13 governance roles + POLICY_DENIED on unmapped admin role

- ROLES += LEDGER_OPERATOR, AUDITOR (05 s11.3); ROLE_MAP stays empty (fail-closed)
- refundCreate/correctionCreate: remove ?? 'OPS_OPERATOR' fallback ->
  DomainException(POLICY_DENIED) when admin has no governance role
- contract test: count=13, no dupes, new roles present
Evidence: suite green; independent review APPROVED"

Commit @(
    "$B/sql/20260820_v2_api_routes_seed.sql"
) "fix(backend): dedupe api_v2 route seed keys (GET routes no longer swallowed)

- regenerate md5(api_v2|METHOD|URL) keys for robot upgradeOrders POST and
  prediction orderCreate POST rows that previously collided with GET rows;
  UTF-16LE encoding preserved (BOM FF FE verified)
Evidence: 49/49 unique keys; openapi lint L5 clean"

Commit @(
    "$B/openapi/lint.php", "$B/openapi/gainode-v2.yaml",
    "$B/openapi/paths/robot.yaml", "$B/openapi/paths/prediction.yaml",
    "$B/openapi/paths/apt_otc.yaml", "$B/openapi/paths/policy_parameter.yaml"
) "feat(backend): OpenAPI lint + close 6 seed<->contract gaps

- lint.php: L1 bidirectional main/path-file coverage, L2 operationId rules,
  L3 write idempotency header, L4 ref resolution incl same-file, L5 seed reconciliation
- add 6 seed-registered read endpoints missing from contract:
  robot_action_list, robot_upgrade_order_list, prediction_order_list,
  prediction_consent_receipt_list, otc_eligibility, parameter_active_release
Evidence: php openapi/lint.php -> PASS 0 error (operations 77->83)"

Commit @(
    'gainode_h5_v2/src', 'gainode_h5_v2/README.md'
) "refactor(h5): remove V1 dead-code cluster (34 files), single-track i18n

- delete legacy views (login/my/team/root Home/MainLayout/RootShell/robot V1/
  ComingSoon), api/legacy.ts+services.ts, stores/user+project,
  ConfirmDialog/CountryPicker/PageHeader/ToastContainer, locales zh-CN.ts/en-US.ts
- i18n/index.ts: drop legacy dict dual-track t(); keep vue-i18n + param interpolation
- locales/*.json: add missing key page.m_power_001.available (7 languages)
- http.ts doRefresh: attach base headers (Accept-Language/X-Request-Id/X-Timestamp)
- README: replace scaffold residue with project doc
Evidence: vitest 23 files / 147 tests PASS; vue-tsc --build PASS"

Commit @(
    'gainode_admin_v2/src/main.ts', 'gainode_admin_v2/.env.example',
    'gainode_admin_v2/.gitignore', 'gainode_admin_v2/src/mockjs/user.ts',
    'gainode_admin_v2/src/views'
) "fix(admin): env-gate mockjs, remove backdoor login, fail-closed mock buttons (20 files)

- main.ts: mockjs only when DEV && VITE_ENABLE_MOCK==='true' (dynamic import);
  .env.example added (+!.env.example in .gitignore)
- mockjs/user.ts: delete getLogin backdoor (admin/123456)
- 20 views: fake-success buttons -> disabled + el-tooltip(FAIL_CLOSED reason),
  fake handlers/local state mutations removed (incl EmergencyControl execute,
  Todo claim/transfer, KycQueue decide, ListPage REAL calls correctly skipped)
Evidence: grep ElMessage.success in src/views -> only 2 REAL spots remain;
  vue-tsc --noEmit EXIT=0 (3 rounds); batch-1 independent review APPROVED"

Commit @(
    "$B/support/exception/AuthorizeException.php",
    "$B/support/controller/ApiV2.php",
    "$B/tests/Contract/PredictionControllerOrderCreateContractTest.php",
    "$B/tests/Contract/OtcOrderCreateControllerContractTest.php",
    "$B/tests/Contract/RobotUpgradeOrderCreateControllerContractTest.php"
) "test(backend)+fix: controller-level contract suites; BE-11 unauth 401 alignment

- 3 suites: real support\Request + getTokenUser full chain; unauth error envelope,
  DEPENDENCY_UNAVAILABLE fail-closed 503, zero-DB-side-effect negative assertions
- BE-11 (issue-20260825-0002): AuthorizeException extends DomainException carrying
  AUTH_UNAUTHENTICATED -> envelopeError maps 401 per contract 05 s7 (was 500)
Evidence: run_all TOTAL 29 / SUITE PASS 29 / exit 0; suites independently APPROVED"

Commit @(
    '.project-ai', 'PRODUCT_OVERVIEW.md', 'README.md'
) "docs(project): workspace audit V1, decision requests, review records, plans

- reviews/: WORKSPACE-AUDIT-V1 (four-end audit + fix log F-01..F-10),
  REVIEW-admin-batch, REVIEW-backend-controller-tests, BE-11 conflict/resolution log
- decisions/: DECISION_REQUESTS_20260822 (DR-01..08, recommendations + impact)
- plans/: NEXT-02 five-state design, BE11-FIX-SPEC, COMMIT-PLAN"

git log --oneline -8
