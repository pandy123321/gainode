<?php

namespace library\response\api;
use support\extend\Response;

class TeamResponse extends Response{

    // 定义信息
    protected array $fields  =   [
        'page'       => ['type' => 'int',    'description' => '当前页码'],
        'size'       => ['type' => 'int',    'description' => '每页数量'],
        'count'      => ['type' => 'int',    'description' => '总记录数'],
        'total_page' => ['type' => 'int',    'description' => '总页数'],
        'data'       => ['type' => 'array',  'description' => '数据列表', 'children' => 'listItem'],

        "user_id"       => ["type" => "integer",   "description"=>"用户ID"],
        "account"       => ["type" => "string",   "description"=>"用户账号"],
        "invite_code"       => ["type" => "string",   "description"=>"邀请码"],
        "parent_id"       => ["type" => "integer",   "description"=>"上级邀请人ID"],
        "parent_level"       => ["type" => "integer",   "description"=>"上级层级"],
        "parent_path"       => ["type" => "string",   "description"=>"上级邀请节点"],
        "invite_path"       => ["type" => "string",   "description"=>"下级邀请节点"],
        "invite_cnt"       => ["type" => "integer",   "description"=>"直推人数"],
        "invite_income_money"       => ["type" => "float",   "description"=>"直推收益金额"],
        "invite_money"       => ["type" => "float",   "description"=>"直推业绩"],
        "invite_paid_money"       => ["type" => "float",   "description"=>"直推支付金额"],
        "team_cnt"       => ["type" => "integer",   "description"=>"团队人数"],
        "team_income_money"       => ["type" => "float",   "description"=>"团队收益金额"],
        "team_money"       => ["type" => "float",   "description"=>"团队业绩"],
        "team_paid_money"       => ["type" => "float",   "description"=>"团队支付金额"],
        "order_cnt"       => ["type" => "integer",   "description"=>"订单数量"],
        "order_money"       => ["type" => "float",   "description"=>"消费金额"],
        "invite_order_money"       => ["type" => "float",   "description"=>"直推消费金额"],
        "team_order_money"       => ["type" => "float",   "description"=>"团队消费金额"],
        "total_fee"       => ["type" => "float",   "description"=>"累计手续费"],
        "team_income_fee"       => ["type" => "float",   "description"=>"团队手续费收益"],
        "reward"       => ["type" => "float",   "description"=>"邀请奖励金"],
        "created_time"       => ["type" => "integer",   "description"=>"创建时间"],
        "updated_time"       => ["type" => "integer",   "description"=>"修改时间"],
        "status"       => ["type" => "integer",   "description"=>"状态(1:可用)"],
        "user_no"       => ["type" => "string",   "description"=>"用户编码"],
        "is_arbitrage"       => ["type" => "integer",   "description"=>"是否开启套利(1:开启,0:关闭)"],
        "is_verify"       => ["type" => "integer",   "description"=>"是否认证(0:未提交,1:待验证审核,2:审核通过,3:已拒绝)"],
        'arbitrage_balance' => ["type" => "string",   "description"=>"套利余额"],
    ];


    protected array $children = [
        'listItem' => ["user_id","account","invite_code","parent_id","parent_level","parent_path","invite_path","invite_cnt","invite_income_money","invite_money","invite_paid_money","team_cnt","team_income_money","team_money","team_paid_money","order_cnt","order_money","invite_order_money","team_order_money","total_fee","team_income_fee","reward","created_time","updated_time","status","user_no","is_arbitrage","is_verify","arbitrage_balance"],
    ];

    //定义场景
    protected array $scenes = [
        'list'   => ['page', 'size', 'count', 'total_page', 'data'],
        'detail'  =>  ["user_id","account","invite_code","parent_id","parent_level","parent_path","invite_path","invite_cnt","invite_income_money","invite_money","invite_paid_money","team_cnt","team_income_money","team_money","team_paid_money","order_cnt","order_money","invite_order_money","team_order_money","total_fee","team_income_fee","reward","created_time","updated_time","status","user_no","is_arbitrage","is_verify","arbitrage_balance"],
    ];
}
