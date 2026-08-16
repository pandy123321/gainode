<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $file_id
 * @property string $file_hash 文件hash值
 * @property string $source 类型
 * @property integer $user_id 用户ID
 * @property string $from_type 类型
 * @property string $engine 存储引擎
 * @property string $file_name 图片名称
 * @property string $file_path 文件路径
 * @property string $origin_name 原始图片名称
 * @property string $file_url 图片地址
 * @property string $file_ext 后缀名称
 * @property integer $file_size 文件大小
 * @property integer $width 宽度
 * @property integer $height 高度
 * @property integer $created_time 创建时间
 * @property integer $updated_time 最后修改时间
 */
class UploadFilesModel extends Model
{
    public $table = 'sys_upload_files';
    public $primaryKey = 'file_id';
    public $connection = 'mysql';
    public $delete_field = null;
    public $fields=[
		"file_id",
		"file_hash",
		"source",
		"user_id",
		"from_type",
		"engine",
		"file_name",
		"file_path",
		"origin_name",
		"file_url",
		"file_ext",
		"file_size",
		"width",
		"height",
		"created_time",
		"updated_time",
    ];

    public function getFileUrl(){
        if($this->engine=='local'){
            return url('/upload/'.$this->file_url);
        }
        return $this->file_url;
    }
}
