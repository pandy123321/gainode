<?php

declare(strict_types=1);

namespace app\admin\controller\v2;

use library\dict\ErrorDict;
use library\dao\robot\RobotRewardDao;
use library\dao\robot\RobotUpgradeOrderDao;
use library\service\admin\AdminApprovalDtoService;
use library\service\admin\AdminConfigDtoService;
use library\service\admin\AdminCorrectionDtoService;
use library\service\admin\AdminLedgerDtoService;
use library\service\admin\AdminLedgerEntriesDtoService;
use library\service\admin\AdminLedgerOverviewDtoService;
use library\service\admin\AdminKycDtoService;
use library\service\admin\AdminOtcDtoService;
use library\service\admin\AdminOtcTradeDtoService;
use library\service\admin\AdminPowerDtoService;
use library\service\admin\AdminPredictionDtoService;
use library\service\admin\AdminPredictionOrderDtoService;
use library\service\admin\AdminPredictionResultDtoService;
use library\service\admin\AdminRefundDtoService;
use library\service\admin\AdminRewardDtoService;
use library\service\admin\AdminRiskDtoService;
use library\service\admin\AdminRobotDtoService;
use library\service\admin\AdminSettlementDtoService;
use library\service\admin\AdminSettlementBatchDtoService;
use library\service\admin\AdminTicketDtoService;
use library\service\admin\AdminUpgradeOrderDtoService;
use library\service\admin\AdminUserDtoService;
use library\service\admin\AdminUserDetailDtoService;
use library\service\admin\AdminWorkbenchDtoService;
use library\service\approval\ApprovalRequestService;
use library\service\audit\AuditEventService;
use library\service\otc\OtcOrderService;
use library\service\otc\OtcTradeService;
use library\service\parameter\ParameterReleaseService;
use library\service\power\PowerPositionService;
use library\service\prediction\CorrectionCaseService;
use library\service\prediction\PredictionMarketService;
use library\service\prediction\RefundCaseService;
use library\service\prediction\ResultService;
use library\service\prediction\SettlementBatchService;
use library\service\prediction\SettlementService;
use library\service\risk\RiskCaseService;
use library\service\robot\RobotService;
use library\service\support\TicketService;
use support\controller\ApiV2;
use support\exception\DomainException;
use support\Response;

/**
 * Admin V2 控制器（admin 应用，AdminAuth 认证；OPTION_A 落地）。
 *
 * 路径契约：/api/v1/admin/...（由 admin 应用 /api/v1/admin 组注册）。
 * $request->app='admin'（控制器位于 app/admin/controller/v2）→ getTokenUser() 走 AdminAuth。
 *
 * 只读：
 *   GET /api/v1/admin/audit-log → 审计日志查询（AuditEventService::listAdmin，脱敏白名单）
 *   GET /api/v1/admin/async-jobs/{id} → 异步任务详情（AsyncJob 无服务，fail-closed）
 *
 * 写操作（市场/结算/退款/更正/案件/审批/导出）：
 *   依赖 admin 角色(05 13 角色)与 sys_admin.role_id 的映射——该映射为另一决策点，
 *   本阶段写操作一律 fail-closed（DEPENDENCY_UNAVAILABLE/POLICY_DENIED），不绑定真实服务，
 *   避免在角色映射未冻结时开放有经济副作用的写路径。
 */
class AdminV2Controller extends ApiV2
{
    /** GET /api/v1/admin/audit-log */
    public function auditLog(): Response
    {
        try {
            $this->request->getTokenUser(); // admin 应用 → AdminAuth
            $filters = [];
            foreach (['actor_id', 'event_code', 'target_object_type', 'target_object_id', 'outcome', 'request_id'] as $k) {
                $v = $this->request->get($k);
                if ($v !== null && $v !== '') {
                    $filters[$k] = (string) $v;
                }
            }
            $result = (new AuditEventService())->listAdmin($filters);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/admission/users — 用户列表 DTO（A-USER-001） */
    public function users(): Response
    {
        try {
            $this->request->getTokenUser();
            $page = (int) $this->request->get('page', 1);
            $size = (int) $this->request->get('size', 20);
            $keyword = (string) $this->request->get('keyword', '');
            $result = (new AdminUserDtoService())->list($page, $size, $keyword);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/otc/orders — OTC 订单列表 DTO（A-OTC-001） */
    public function otcOrders(): Response
    {
        try {
            $this->request->getTokenUser();
            $page = (int) $this->request->get('page', 1);
            $size = (int) $this->request->get('size', 20);
            $status = (string) $this->request->get('status', '');
            $result = (new AdminOtcDtoService())->list($page, $size, $status);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/otc/users/{id}/orders — 用户 OTC 订单（A-OTC-001 补充） */
    public function otcUserOrders(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new OtcOrderService())->listByUser($id);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/otc/trades — OTC 成交记录 DTO（A-OTC-001 补充） */
    public function otcTrades(): Response
    {
        try {
            $this->request->getTokenUser();
            $page = (int) $this->request->get('page', 1);
            $size = (int) $this->request->get('size', 20);
            $otcOrderId = (string) $this->request->get('otc_order_id', '');
            $result = (new AdminOtcTradeDtoService())->list($page, $size, $otcOrderId);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/otc/trades/{id} — OTC 成交详情（A-OTC-001） */
    public function otcTradeDetail(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new OtcTradeService())->detail($id);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/robot/list — Robot 列表 DTO（A-ROBOT-001） */
    public function robots(): Response
    {
        try {
            $this->request->getTokenUser();
            $page = (int) $this->request->get('page', 1);
            $size = (int) $this->request->get('size', 20);
            $status = (string) $this->request->get('status', '');
            $result = (new AdminRobotDtoService())->list($page, $size, $status);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/robot/upgrade-orders — Robot 升级订单 DTO（A-ROBOT-001 补充） */
    public function upgradeOrders(): Response
    {
        try {
            $this->request->getTokenUser();
            $page = (int) $this->request->get('page', 1);
            $size = (int) $this->request->get('size', 20);
            $status = (string) $this->request->get('status', '');
            $result = (new AdminUpgradeOrderDtoService())->list($page, $size, $status);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/robot/upgrade-orders/{id} — Robot 升级订单详情（A-ROBOT-001） */
    public function robotUpgradeOrderDetail(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $o = (new RobotUpgradeOrderDao())->get($id);
            if (empty($o)) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'upgrade order not found');
            }
            return $this->envelope([
                'upgrade_order_id'   => (string) $o->upgrade_order_id,
                'robot_id'           => (string) $o->robot_id,
                'user_id'            => (string) $o->user_id,
                'from_level'         => (int) $o->from_level,
                'to_level'           => (int) $o->to_level,
                'apt_cost'           => (string) $o->apt_cost,
                'status'             => (string) $o->status,
                'power_cap_after'    => (string) $o->power_cap_after,
                'capacities_after'   => $o->capacities_after !== null ? (string) $o->capacities_after : null,
                'cooling_end_at'     => (int) $o->cooling_end_at,
                'review_case_id'     => (string) $o->review_case_id,
                'approval_id'        => (string) $o->approval_id,
                'ledger_entry_id'    => (string) $o->ledger_entry_id,
                'rule_version'       => (string) $o->rule_version,
                'parameter_release_id' => (string) $o->parameter_release_id,
                'object_version'     => (int) $o->object_version,
                'created_time'       => (int) $o->getRawOriginal('created_time'),
            ]);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/support/tickets — 工单队列 DTO（A-SUPPORT-001） */
    public function tickets(): Response
    {
        try {
            $this->request->getTokenUser();
            $page = (int) $this->request->get('page', 1);
            $size = (int) $this->request->get('size', 20);
            $status = (string) $this->request->get('status', '');
            $result = (new AdminTicketDtoService())->list($page, $size, $status);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/ledger/accounts — APT 账户列表 DTO（A-LEDGER-002） */
    public function ledgerAccounts(): Response
    {
        try {
            $this->request->getTokenUser();
            $page = (int) $this->request->get('page', 1);
            $size = (int) $this->request->get('size', 20);
            $keyword = (string) $this->request->get('keyword', '');
            $result = (new AdminLedgerDtoService())->list($page, $size, $keyword);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/risk/cases — Risk Case 列表 DTO（A-RISK-001） */
    public function riskCases(): Response
    {
        try {
            $this->request->getTokenUser();
            $page = (int) $this->request->get('page', 1);
            $size = (int) $this->request->get('size', 20);
            $status = (string) $this->request->get('status', '');
            $severity = (string) $this->request->get('severity', '');
            $result = (new AdminRiskDtoService())->list($page, $size, $status, $severity);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/risk/cases/{id} — Risk Case 详情（A-RISK-001 详情） */
    public function riskDetail(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new RiskCaseService())->detail($id);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/approval/tasks — 审批中心 DTO（A-APPROVAL-001） */
    public function approvalTasks(): Response
    {
        try {
            $this->request->getTokenUser();
            $page = (int) $this->request->get('page', 1);
            $size = (int) $this->request->get('size', 20);
            $status = (string) $this->request->get('status', '');
            $result = (new AdminApprovalDtoService())->list($page, $size, $status);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/approval/tasks/{id} — 审批详情（A-APPROVAL-001 详情） */
    public function approvalDetail(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new ApprovalRequestService())->detail($id);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/parameter/releases/{id} — Parameter Release 详情（A-CONFIG-002） */
    public function parameterReleaseDetail(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new ParameterReleaseService())->detail($id);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/parameter/definitions — Parameter Center DTO（A-CONFIG-001） */
    public function parameterDefinitions(): Response
    {
        try {
            $this->request->getTokenUser();
            $page = (int) $this->request->get('page', 1);
            $size = (int) $this->request->get('size', 20);
            $status = (string) $this->request->get('status', '');
            $result = (new AdminConfigDtoService())->list($page, $size, $status);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/prediction/markets — Market 列表 DTO（A-PREDICT-001） */
    public function predictionMarkets(): Response
    {
        try {
            $this->request->getTokenUser();
            $page = (int) $this->request->get('page', 1);
            $size = (int) $this->request->get('size', 20);
            $status = (string) $this->request->get('status', '');
            $result = (new AdminPredictionDtoService())->list($page, $size, $status);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/prediction/markets/{id} — Market 详情（A-PREDICT-002） */
    public function predictionMarketDetail(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new PredictionMarketService())->detail($id);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/prediction/orders — Prediction Order 列表 DTO（A-PREDICT-001 补充） */
    public function predictionOrders(): Response
    {
        try {
            $this->request->getTokenUser();
            $page = (int) $this->request->get('page', 1);
            $size = (int) $this->request->get('size', 20);
            $status = (string) $this->request->get('status', '');
            $result = (new AdminPredictionOrderDtoService())->list($page, $size, $status);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/prediction/results — Result/Settlement 列表 DTO（A-PREDICT-003） */
    public function predictionResults(): Response
    {
        try {
            $this->request->getTokenUser();
            $page = (int) $this->request->get('page', 1);
            $size = (int) $this->request->get('size', 20);
            $status = (string) $this->request->get('status', '');
            $result = (new AdminPredictionResultDtoService())->list($page, $size, $status);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/prediction/results/{id} — Result 详情（A-PREDICT-003） */
    public function resultDetail(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new ResultService())->detail($id);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/prediction/settlements — Settlement 列表 DTO（A-PREDICT-003 结算） */
    public function settlements(): Response
    {
        try {
            $this->request->getTokenUser();
            $page = (int) $this->request->get('page', 1);
            $size = (int) $this->request->get('size', 20);
            $status = (string) $this->request->get('status', '');
            $result = (new AdminSettlementDtoService())->list($page, $size, $status);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/prediction/settlement-batches — Settlement Batch 列表 DTO（A-PREDICT-003） */
    public function settlementBatches(): Response
    {
        try {
            $this->request->getTokenUser();
            $page = (int) $this->request->get('page', 1);
            $size = (int) $this->request->get('size', 20);
            $status = (string) $this->request->get('status', '');
            $result = (new AdminSettlementBatchDtoService())->list($page, $size, $status);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/prediction/settlement-batches/{id} — Settlement Batch 详情（A-PREDICT-003） */
    public function settlementBatchDetail(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new SettlementBatchService())->detail($id);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/prediction/settlements/{id} — Settlement 详情（A-PREDICT-003） */
    public function settlementDetail(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new SettlementService())->detail($id);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/prediction/refunds — Refund/Correction 列表 DTO（A-PREDICT-004） */
    public function refunds(): Response
    {
        try {
            $this->request->getTokenUser();
            $page = (int) $this->request->get('page', 1);
            $size = (int) $this->request->get('size', 20);
            $status = (string) $this->request->get('status', '');
            $result = (new AdminRefundDtoService())->list($page, $size, $status);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/prediction/refunds/{id} — Refund 详情（A-PREDICT-004） */
    public function refundDetail(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new RefundCaseService())->detail($id);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/prediction/corrections/{id} — Correction 详情（A-PREDICT-004） */
    public function correctionDetail(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new CorrectionCaseService())->detail($id);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/prediction/corrections — Correction 列表 DTO（A-PREDICT-004 更正） */
    public function corrections(): Response
    {
        try {
            $this->request->getTokenUser();
            $page = (int) $this->request->get('page', 1);
            $size = (int) $this->request->get('size', 20);
            $status = (string) $this->request->get('status', '');
            $result = (new AdminCorrectionDtoService())->list($page, $size, $status);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/power/accounts — Power 账户列表 DTO（A-POWER-001） */
    public function powerAccounts(): Response
    {
        try {
            $this->request->getTokenUser();
            $page = (int) $this->request->get('page', 1);
            $size = (int) $this->request->get('size', 20);
            $result = (new AdminPowerDtoService())->list($page, $size);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/power/users/{id} — Power 账户详情（A-POWER-001 详情） */
    public function powerDetail(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $pos = (new PowerPositionService())->getByUser($id);
            if (empty($pos)) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'power position not found');
            }
            return $this->envelope([
                'user_id'                  => (string) $pos->user_id,
                'available'                => (string) $pos->available,
                'frozen'                   => (string) $pos->frozen,
                'consumed_period'          => (string) $pos->consumed_period,
                'released_period'          => (string) $pos->released_period,
                'recovering'               => (string) $pos->recovering,
                'limit'                    => (string) $pos->limit,
                'power_cap_source_robot_level' => (int) $pos->power_cap_source_robot_level,
                'last_restore_at'          => (int) $pos->last_restore_at,
                'next_restore_at'          => (int) $pos->next_restore_at,
                'rule_version'             => (string) $pos->rule_version,
                'object_version'           => (int) $pos->object_version,
            ]);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/robot/rewards — Reward 运营 DTO（A-ROBOT-003） */
    public function rewardOps(): Response
    {
        try {
            $this->request->getTokenUser();
            $page = (int) $this->request->get('page', 1);
            $size = (int) $this->request->get('size', 20);
            $state = (string) $this->request->get('state', '');
            $result = (new AdminRewardDtoService())->list($page, $size, $state);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/robot/rewards/{id} — Robot Reward 详情（A-ROBOT-003） */
    public function robotRewardDetail(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $r = (new RobotRewardDao())->get($id);
            if (empty($r)) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'reward not found');
            }
            return $this->envelope([
                'reward_id'               => (string) $r->reward_id,
                'user_id'                 => (string) $r->user_id,
                'robot_id'                => (string) $r->robot_id,
                'period'                  => (string) $r->period,
                'standard_capacity'       => (string) $r->standard_capacity,
                'daily_reward_coefficient'=> (string) $r->daily_reward_coefficient,
                'quantity_apt'            => (string) $r->quantity_apt,
                'state'                   => (string) $r->state,
                'eligibility_snapshot_id' => (string) $r->eligibility_snapshot_id,
                'budget_snapshot_id'      => (string) $r->budget_snapshot_id,
                'claim_id'                => (string) $r->claim_id,
                'ledger_entry_id'         => (string) $r->ledger_entry_id,
                'expires_at'              => (int) $r->expires_at,
                'rule_version'            => (string) $r->rule_version,
                'object_version'          => (int) $r->object_version,
                'created_time'            => (int) $r->getRawOriginal('created_time'),
            ]);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/otc/orders/{id} — OTC 订单详情（A-OTC-002） */
    public function otcOrderDetail(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new OtcOrderService())->detail($id);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/robot/{id} — Robot 详情（A-ROBOT-002） */
    public function robotDetail(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new RobotService())->detail($id);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/support/tickets/{id} — 工单详情（A-SUPPORT-002） */
    public function ticketDetail(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new TicketService())->detail($id);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/workbench/overview — 运营总览 DTO（A-WORK-001） */
    public function workbenchOverview(): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new AdminWorkbenchDtoService())->overview();
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/audit-log/{id} — 审计日志详情（A-AUDIT-001） */
    public function auditLogDetail(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new AuditEventService())->detail($id);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/ledger/overview — 资产总览 DTO（A-LEDGER-001） */
    public function ledgerOverview(): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new AdminLedgerOverviewDtoService())->overview();
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/ledger/entries — APT 流水明细 DTO（A-LEDGER-002 明细） */
    public function ledgerEntries(): Response
    {
        try {
            $this->request->getTokenUser();
            $page = (int) $this->request->get('page', 1);
            $size = (int) $this->request->get('size', 20);
            $accountId = (string) $this->request->get('account_id', '');
            $result = (new AdminLedgerEntriesDtoService())->list($page, $size, $accountId);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/admission/kyc — KYC 审核队列 DTO（A-KYC-001） */
    public function kycQueue(): Response
    {
        try {
            $this->request->getTokenUser();
            $page = (int) $this->request->get('page', 1);
            $size = (int) $this->request->get('size', 20);
            $status = (string) $this->request->get('status', '');
            $result = (new AdminKycDtoService())->list($page, $size, $status);
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/admission/users/{id} — 用户 360 详情 DTO（A-USER-002） */
    public function userDetail(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            $result = (new AdminUserDetailDtoService())->detail($id);
            if ($result === null) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'user not found');
            }
            return $this->envelope($result);
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** GET /api/v1/admin/async-jobs/{id} — AsyncJob 服务不存在 → fail-closed */
    public function asyncJob(string $id): Response
    {
        try {
            $this->request->getTokenUser();
            throw new DomainException(ErrorDict::DEPENDENCY_UNAVAILABLE, 'async job service not available');
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }

    /** POST /api/v1/admin/export-tasks — fail-closed（export 服务不存在） */
    public function exportTask(): Response
    {
        try {
            $this->request->getTokenUser();
            throw new DomainException(ErrorDict::DEPENDENCY_UNAVAILABLE, 'export task service not available');
        } catch (\Throwable $e) {
            return $this->envelopeError($e);
        }
    }
}
