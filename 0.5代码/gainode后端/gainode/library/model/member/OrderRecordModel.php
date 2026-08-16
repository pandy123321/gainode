<?php

namespace library\model\member;

use support\extend\Model;

/**
 * @property integer $id
 * @property integer $user_id 用户ID
 * @property string $type 类型 recharge,withdraw,transfer,profit
 * @property string $from_acc 来源账户
 * @property string $to_acc 转向帐户
 * @property string $currency 币种
 * @property string $amount 金额
 * @property string $descr 描述
 * @property string $ref_table 来源表
 * @property integer $ref_id 来源 ID
 * @property integer $created_time 创建时间
 * @property integer $updated_time 修改时间
 * @property string $status 状态(0:待处理,1:已完成,2:失败,-1:删除)
 */
class OrderRecordModel extends Model
{
    public $table = 'member_order_record';
    public $primaryKey = 'id';
    public $connection = 'mysql';

    public $fields=[
		"id",
		"user_id",
		"type",
		"from_acc",
		"to_acc",
		"currency",
		"amount",
		"descr",
		"ref_table",
		"ref_id",
		"created_time",
		"updated_time",
		"status",
    ];

    // ── 状态常量 ──────────────────────────────────────────────
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED  = 'failed';
    const STATUS_DELETED  = 'deleted';

    // ── 类型常量 ──────────────────────────────────────────────
    const TYPE_RECHARGE = 'recharge';
    const TYPE_WITHDRAW = 'withdraw';

    const TYPE_PROJECT   = 'project';
    const TYPE_TRANSFER = 'transfer';
    const TYPE_PROFIT   = 'profit';

    protected $appends = ['status_text', 'type_text'];

    // ── 可读标签 ──────────────────────────────────────────────

    public function getStatusTextAttribute(): string
    {
        return match ($data['status']??'') {
            self::STATUS_SUCCESS => '已完成',
            self::STATUS_FAILED  => '失败',
            self::STATUS_DELETED => '已删除',
            default              => '处理中',
        };
    }

    public function getTypeTextAttribute(): string
    {
        return match ($data['type'] ?? '') {
            self::TYPE_RECHARGE  => '充值',
            self::TYPE_WITHDRAW => '提现',
            self::TYPE_TRANSFER => '划转',
            self::TYPE_PROFIT   => '收益',
            self::TYPE_PROJECT=> '矿机',
            default => $data['type'] ?? '',
        };
    }

    public function user()
    {
        return $this->hasOne(UserModel::class, 'id', 'user_id');
    }
}
