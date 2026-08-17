<?php

declare(strict_types=1);

namespace library\service\support;

use library\dao\support\TicketDao;
use library\dict\ErrorDict;
use library\model\audit\AuditEventModel;
use library\model\support\TicketModel;
use library\service\audit\AuditEventService;
use library\service\transaction\TransactionBoundary;
use support\extend\Db;
use support\extend\Service;
use support\exception\DomainException;
use support\middleware\RequestContext;
use support\utils\Random;

/**
 * 工单 Service — tickets 表唯一 Authoritative Writer（S02-P07）
 *
 * @authoritative_writer tickets
 *
 * 状态机（05 §4 canonical Ticket，冻结；转移矩阵 2B-2 §7.2 TK1–TK8，CANDIDATE 未 FROZEN）：
 *   submitted → in_progress ⇄ waiting_user / under_review → resolved → closed
 *   - TK8 resolved → in_progress 重开（appeal_eligible 且要求重开）。
 *
 * 状态分类（2B-2 §7.1）：
 *   - TRUE_TERMINAL：closed
 *   - STABLE：resolved（可重开或关闭）
 *   - INTERMEDIATE：in_progress / waiting_user / under_review
 *
 * 角色映射（2B-2 §2）：处理 → SUPPORT_AGENT；用户回复 → END_USER（无 SoD 互斥）。
 *
 * 实现策略（fail-closed，与 S02-P05/P06 一致）：
 *   - 纯状态转移（TK1–TK8）完整实现（审计 + object_version CAS + audit_event_id 回写）。
 *   - 每次状态转移更新 last_activity_at。
 *   - TK5/TK6 触发事件/Writer 相同，合并为单一 resolve（from in_progress/under_review）。
 *
 * @method TicketModel create($data)
 * @method TicketModel get($id, string $field = null)
 * @method TicketModel find($id)
 * @method TicketModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class TicketService extends Service
{
    public const EVENT_ACCEPTED      = 'TICKET_ACCEPTED';
    public const EVENT_WAITING_USER  = 'TICKET_WAITING_USER';
    public const EVENT_USER_REPLIED  = 'TICKET_USER_REPLIED';
    public const EVENT_UNDER_REVIEW  = 'TICKET_UNDER_REVIEW';
    public const EVENT_RESOLVED      = 'TICKET_RESOLVED';
    public const EVENT_CLOSED        = 'TICKET_CLOSED';
    public const EVENT_REOPENED      = 'TICKET_REOPENED';

    // ---- 05 §8 最小角色（本包仅引用这 2 个，canonical 冻结）----
    public const ROLE_END_USER      = 'END_USER';
    public const ROLE_SUPPORT_AGENT = 'SUPPORT_AGENT';

    public function __construct()
    {
        $this->dao = TicketDao::class;
        parent::__construct();
    }

    public function getByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }

    public function getByIdempotencyKey(string $idempotencyKey)
    {
        return $this->getNewDao()->getByIdempotencyKey($idempotencyKey);
    }

    public function listByUser(string $userId): array
    {
        $items = [];
        foreach ($this->getByUser($userId) as $t) {
            $items[] = [
                'ticket_id'     => (string) $t->ticket_id,
                'category'      => (string) $t->category,
                'status'        => (string) $t->status,
                'assigned_to'   => (string) $t->assigned_to,
                'created_time'  => (int) $t->getRawOriginal('created_time'),
            ];
        }
        return ['tickets' => $items];
    }

    public function detail(string $ticketId): array
    {
        $t = $this->get($ticketId);
        if (empty($t)) {
            throw new DomainException(ErrorDict::VALIDATION_ERROR, 'ticket not found');
        }
        return [
            'ticket_id'               => (string) $t->ticket_id,
            'user_id'                 => (string) $t->user_id,
            'category'                => (string) $t->category,
            'status'                  => (string) $t->status,
            'assigned_to'             => (string) $t->assigned_to,
            'last_activity_at'        => (int) $t->last_activity_at,
            'resolution_type'         => (string) $t->resolution_type,
            'resolution_summary_key'  => (string) $t->resolution_summary_key,
            'appeal_eligible'         => (int) $t->appeal_eligible,
            'object_version'          => (int) $t->object_version,
        ];
    }

    /** TK1：submitted → in_progress（SUPPORT_AGENT 接受，分配处理人） */
    public function accept(string $ticketId, string $actorId, string $actorRole, string $assignedTo = ''): TicketModel
    {
        $extra = [];
        if ($assignedTo !== '') {
            $extra['assigned_to'] = $assignedTo;
        }
        return $this->transition(
            $ticketId, [TicketModel::STATUS_SUBMITTED], TicketModel::STATUS_IN_PROGRESS,
            self::EVENT_ACCEPTED, $actorId, $actorRole, $extra,
            fn (TicketModel $t) => $this->guardRole([self::ROLE_SUPPORT_AGENT], $actorRole)
        );
    }

    /** TK2：in_progress → waiting_user（等待用户回复；SUPPORT_AGENT） */
    public function waitUser(string $ticketId, string $actorId, string $actorRole): TicketModel
    {
        return $this->transition(
            $ticketId, [TicketModel::STATUS_IN_PROGRESS], TicketModel::STATUS_WAITING_USER,
            self::EVENT_WAITING_USER, $actorId, $actorRole,
            [],
            fn (TicketModel $t) => $this->guardRole([self::ROLE_SUPPORT_AGENT], $actorRole)
        );
    }

    /** TK3：waiting_user → in_progress（用户回复；END_USER） */
    public function userReplied(string $ticketId, string $actorId, string $actorRole): TicketModel
    {
        return $this->transition(
            $ticketId, [TicketModel::STATUS_WAITING_USER], TicketModel::STATUS_IN_PROGRESS,
            self::EVENT_USER_REPLIED, $actorId, $actorRole,
            [],
            fn (TicketModel $t) => $this->guardRole([self::ROLE_END_USER], $actorRole)
        );
    }

    /** TK4：in_progress → under_review（升级复核；SUPPORT_AGENT） */
    public function escalate(string $ticketId, string $actorId, string $actorRole): TicketModel
    {
        return $this->transition(
            $ticketId, [TicketModel::STATUS_IN_PROGRESS], TicketModel::STATUS_UNDER_REVIEW,
            self::EVENT_UNDER_REVIEW, $actorId, $actorRole,
            [],
            fn (TicketModel $t) => $this->guardRole([self::ROLE_SUPPORT_AGENT], $actorRole)
        );
    }

    /** TK5/TK6：in_progress / under_review → resolved（问题解决；SUPPORT_AGENT） */
    public function resolve(string $ticketId, string $actorId, string $actorRole, string $resolutionType = ''): TicketModel
    {
        $extra = [];
        if ($resolutionType !== '') {
            $extra['resolution_type'] = $resolutionType;
        }
        return $this->transition(
            $ticketId,
            [TicketModel::STATUS_IN_PROGRESS, TicketModel::STATUS_UNDER_REVIEW],
            TicketModel::STATUS_RESOLVED,
            self::EVENT_RESOLVED, $actorId, $actorRole, $extra,
            fn (TicketModel $t) => $this->guardRole([self::ROLE_SUPPORT_AGENT], $actorRole)
        );
    }

    /** TK7：resolved → closed（确认关闭；SUPPORT_AGENT） */
    public function close(string $ticketId, string $actorId, string $actorRole): TicketModel
    {
        return $this->transition(
            $ticketId, [TicketModel::STATUS_RESOLVED], TicketModel::STATUS_CLOSED,
            self::EVENT_CLOSED, $actorId, $actorRole,
            [],
            fn (TicketModel $t) => $this->guardRole([self::ROLE_SUPPORT_AGENT], $actorRole)
        );
    }

    /** TK8：resolved → in_progress（重开，appeal_eligible=1；END_USER / SUPPORT_AGENT） */
    public function reopen(string $ticketId, string $actorId, string $actorRole): TicketModel
    {
        return $this->transition(
            $ticketId, [TicketModel::STATUS_RESOLVED], TicketModel::STATUS_IN_PROGRESS,
            self::EVENT_REOPENED, $actorId, $actorRole,
            [],
            function (TicketModel $t) use ($actorRole) {
                $this->guardRole([self::ROLE_END_USER, self::ROLE_SUPPORT_AGENT], $actorRole);
                if ((int) $t->appeal_eligible !== 1) {
                    throw new DomainException(ErrorDict::POLICY_DENIED, 'ticket is not appeal eligible');
                }
            }
        );
    }

    private function transition(
        string $ticketId,
        array $fromStatuses,
        string $toStatus,
        string $eventCode,
        string $actorId,
        string $actorRole,
        array $extraFields = [],
        ?callable $guard = null
    ): TicketModel {
        return (new TransactionBoundary())->run(function () use (
            $ticketId, $fromStatuses, $toStatus, $eventCode, $actorId, $actorRole, $extraFields, $guard
        ) {
            $ticket = $this->get($ticketId);
            if (empty($ticket)) {
                throw new DomainException(ErrorDict::VALIDATION_ERROR, 'ticket not found');
            }
            $current = (string) $ticket->status;
            if (!in_array($current, $fromStatuses, true)) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'invalid ticket state transition');
            }
            if ($guard !== null) {
                $guard($ticket);
            }

            $auditId = $this->appendAudit($eventCode, $actorId, $actorRole, $ticketId, $ticket->case_id);

            $fields = array_merge([
                'status'           => $toStatus,
                'last_activity_at' => time(),
                'audit_event_id'   => $auditId,
                'object_version'   => (int) $ticket->object_version + 1,
                'updated_time'     => time(),
            ], $extraFields);

            $affected = Db::connection('mysql')
                ->table('tickets')
                ->where('ticket_id', $ticketId)
                ->where('status', $current)
                ->where('object_version', (int) $ticket->object_version)
                ->update($fields);

            if ($affected !== 1) {
                throw new DomainException(ErrorDict::OBJECT_VERSION_CONFLICT, 'ticket state transition CAS conflict');
            }

            return $this->get($ticketId);
        });
    }

    /** 角色白名单：不在冻结角色集合内 → AUTH_FORBIDDEN（fail-closed） */
    private function guardRole(array $allowedRoles, string $actorRole): void
    {
        if (!in_array($actorRole, $allowedRoles, true)) {
            throw new DomainException(ErrorDict::AUTH_FORBIDDEN, 'ticket actor role forbidden');
        }
    }

    private function appendAudit(
        string $eventCode,
        string $actorId,
        string $actorRole,
        string $targetObjectId,
        ?string $caseId
    ): string {
        $auditId = (string) Random::getSnowflakeID();
        (new AuditEventService())->create([
            'audit_event_id'       => $auditId,
            'event_code'           => $eventCode,
            'actor_id'             => $actorId,
            'actor_role'           => $actorRole,
            'target_object_type'   => 'tickets',
            'target_object_id'     => $targetObjectId,
            'before_snapshot_type' => '',
            'before_snapshot_id'   => '0',
            'after_snapshot_type'  => '',
            'after_snapshot_id'    => '0',
            'outcome'              => AuditEventModel::OUTCOME_SUCCESS,
            'reason_code'          => '',
            'request_id'           => RequestContext::getRequestId(),
            'approval_id'          => '0',
            'case_id'              => ($caseId !== null && $caseId !== '') ? $caseId : '0',
            'created_time'         => time(),
        ]);
        return $auditId;
    }
}
