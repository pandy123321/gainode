<?php

declare(strict_types=1);

namespace library\service\admin;

use library\dao\approval\ApprovalRequestDao;
use library\dao\kyc\KycCaseDao;
use library\dao\otc\OtcOrderDao;
use library\dao\support\TicketDao;
use library\model\kyc\KycCaseModel;
use support\extend\Service;

/**
 * Admin V2 工作台今日待办 DTO 服务（A-WORK-002）。
 *
 * 只读聚合：各域待办计数（待审批 / 待复核 KYC / 待处理工单 / 待审核 OTC）。
 * 计数使用 Dao::count 简单等值条件（不依赖复杂操作符语法，避免运行时风险）。
 * 供 Admin 2.0 今日待办页经 /api/v1/admin/workbench/todo 对接。
 */
class AdminTodoDtoService extends Service
{
    /**
     * 今日待办聚合。
     *
     * - pending_approvals：待审批（ApprovalRequest.status=pending）
     * - pending_kyc：待复核（KycCase.status=review）
     * - open_tickets：待处理（Ticket.status=submitted）
     * - review_otc：待审核（OtcOrder.status=review）
     *
     * @return array{pending_approvals:int,pending_kyc:int,open_tickets:int,review_otc:int}
     */
    public function todo(): array
    {
        return [
            'pending_approvals' => (int) (new ApprovalRequestDao())->count(['status' => 'pending']),
            'pending_kyc'       => (int) (new KycCaseDao())->count(['status' => KycCaseModel::STATUS_REVIEW]),
            'open_tickets'      => (int) (new TicketDao())->count(['status' => 'submitted']),
            'review_otc'        => (int) (new OtcOrderDao())->count(['status' => 'review']),
        ];
    }
}
