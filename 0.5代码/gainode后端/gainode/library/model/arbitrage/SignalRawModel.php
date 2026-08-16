<?php

namespace library\model\arbitrage;

use support\extend\Model;

/**
 * @property integer $signal_id 关联信号ID(arbitrage_signal.id,1:1)
 * @property string $payload BetBurger原始套利包(JSON,含arb/bet1/bet2等完整字段,用于审计与回放)
 * @property integer $created_time 创建时间戳(Unix秒)
 * @property integer $updated_time 更新时间戳(Unix秒,信号刷新时同步更新)
 */
class SignalRawModel extends Model
{
    public $table = 'arbitrage_signal_raw';
    public $primaryKey = 'signal_id';
    public $incrementing = false;
    public $connection = 'mysql';
    /** 本表无软删字段 */
    public $delete_field = '';
    
    public $fields=[
		"signal_id",
		"payload",
		"created_time",
		"updated_time",
    ];
}
