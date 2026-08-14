<?php

declare(strict_types=1);

namespace library\model\ledger;

use support\extend\Model;

/**
 * apt_accounts 表映射 — APT 数量账主账号表（05 §3 AptAccount，无状态机）
 *
 * 四账分离中的「APT 数量账」。余额为 scalar fields，无领域状态机。
 * 余额字段（balance_apt_* / frozen_apt_* / total_*_apt）只能由 Ledger 模块的
 * Authoritative Writer 在同一事务内随账本分录流转更新，禁止其它路径直接改写。
 *
 * @property string $account_id 账号ID(Snowflake，主键)
 * @property string $user_id 用户ID(引用 member_user.id，V2.0 拟加宽)
 * @property string $balance_apt_i APT-I 可用余额
 * @property string $balance_apt_c APT-C 可用余额(Future，余额结构预留，不代表开通 APT-C 记账能力)
 * @property string $frozen_apt_i APT-I 冻结余额
 * @property string $frozen_apt_c APT-C 冻结余额(Future)
 * @property string $total_earned_apt 历史累计获得 APT 总额
 * @property string $total_spent_apt 历史累计支出 APT 总额
 * @property string $last_ledger_entry_id 最近一笔账本分录ID
 * @property string $rule_version 生效规则版本号
 * @property string $snapshot_id 关联参数快照ID
 * @property int $object_version 并发控制版本号(乐观锁)
 * @property int $created_time 创建时间(Unix秒)
 * @property int $updated_time 更新时间(Unix秒)
 */
class AptAccountModel extends Model
{
    public $table = 'apt_accounts';
    public $primaryKey = 'account_id';
    public $connection = 'mysql';

    // Snowflake 主键（bigint unsigned，应用层生成），非自增；字符串键避免 64 位溢出
    public $incrementing = false;
    public $keyType = 'string';

    // V2.0 核心实体以 ENUM 冻结领域状态，无 V1.x 的 status 软删字段
    public $delete_field = '';

    public $fields = [
        'account_id',
        'user_id',
        'balance_apt_i',
        'balance_apt_c',
        'frozen_apt_i',
        'frozen_apt_c',
        'total_earned_apt',
        'total_spent_apt',
        'last_ledger_entry_id',
        'rule_version',
        'snapshot_id',
        'object_version',
        'created_time',
        'updated_time',
    ];
}
