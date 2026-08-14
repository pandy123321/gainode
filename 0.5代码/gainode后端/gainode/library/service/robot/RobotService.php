<?php

declare(strict_types=1);

namespace library\service\robot;

use library\dao\robot\RobotDao;
use library\model\robot\RobotModel;
use support\extend\Service;

/**
 * Robot 领域 Service — robots 表唯一 Authoritative Writer（STAGE-01 骨架）
 *
 * @authoritative_writer robots
 *
 * 状态机说明（05 §4 canonical，MC1 冻结）：
 *   inactive / active / cooling / review / restricted / paused
 *   - cooling：连续运行后的冷却期，禁止立即重启
 *   - review：风控/异常审计锁定
 *   - restricted：策略受限运行（部分功能禁用）
 *   - paused：管理员手动暂停
 *
 * 本骨架不实现状态转移矩阵（属 Machine Contract 第二批「Event Catalog / 状态转移矩阵」范畴）。
 * 在转移矩阵正式 FROZEN 前，任何状态流转操作 MUST FAIL_CLOSED，不得自创转移规则。
 *
 * @method RobotModel create($data)
 * @method RobotModel get($id, string $field = null)
 * @method RobotModel find($id)
 * @method RobotModel fetch(array $params = [], array $orderBy = [], array $fields = [])
 * @method \Illuminate\Database\Eloquent\Collection fetchAll(array $params = [], array $orderBy = [], array $fields = [])
 */
class RobotService extends Service
{
    public function __construct()
    {
        $this->dao = RobotDao::class;
        parent::__construct();
    }

    /**
     * 按用户查询 Robot 列表（只读透传）
     *
     * @param string $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByUser(string $userId)
    {
        return $this->getNewDao()->getByUser($userId);
    }
}
