<?php

namespace library\model\member;

use support\extend\Model;

/**
 * @property integer $id 主键ID
 * @property integer $user_id 会员ID
 * @property string $real_name 真实姓名
 * @property string $country 国家/地区
 * @property string $id_type 证件类型：(身份证:id_card,护照:passport,驾驶证:driver)
 * @property string $id_number 证件号码
 * @property string $phone 认证手机号
 * @property string $front_image 证件正面图片
 * @property string $back_image 证件反面图片
 * @property string $hand_image 手持证件图片
 * @property string $reject_reason 拒绝原因
 * @property integer $review_admin_id 审核管理员ID
 * @property integer $review_time 审核时间
 * @property string $review_status 审核状态(未审核:created,审核通过:approved,已拒绝:rejected)
 * @property integer $created_time 创建时间
 * @property integer $updated_time 更新时间
 * @property integer $deleted_time 软删除时间
 * @property integer $status 状态：(0:隐藏,1:正常,-1:删除)
 */
class UserKycModel extends Model
{
    public $table = 'member_user_kyc';
    public $primaryKey = 'id';
    public $connection = 'mysql';

    public const STATUS_CREATED = 'created';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public $fields=[
		"id",
		"user_id",
		"real_name",
		"country",
		"id_type",
		"id_number",
		"phone",
		"front_image",
		"back_image",
		"hand_image",
		"reject_reason",
		"review_admin_id",
		"review_time",
		"review_status",
		"created_time",
		"updated_time",
		"deleted_time",
		"status",
    ];



    public function user()
    {
        return $this->hasOne(UserModel::class, 'id', 'user_id');
    }
}
