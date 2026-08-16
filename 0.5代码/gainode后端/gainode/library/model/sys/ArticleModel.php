<?php

namespace library\model\sys;

use support\extend\Model;

/**
 * @property integer $id 文章id
 * @property integer $eid 企业ID(0:平台)
 * @property string $title 文章标题
 * @property string $content 文章内容
 * @property integer $category_id 分类id
 * @property string $image_url 文章图片
 * @property string $link_url 链接地址
 * @property string $author 作者
 * @property integer $is_rec 是否推荐(1:推荐,0:不推荐)
 * @property integer $visit_num 阅读量
 * @property integer $sort 排序
 * @property string $descr 描述
 * @property integer $created_time 创建时间
 * @property integer $updated_time 最后修改时间
 * @property integer $status 状态(1:正常,0:不显示,-1:删除)
 */
class ArticleModel extends Model
{
    public $table = 'sys_article';
    public $primaryKey = 'id';
    public $connection = 'mysql';
    public $fields=[
		"id",
		"eid",
		"title",
		"content",
		"category_id",
		"image_url",
		"link_url",
		"author",
		"is_rec",
		"visit_num",
		"sort",
		"descr",
		"created_time",
		"updated_time",
		"status",
    ];

    protected $appends = ['category_name'];

    public function getCategoryNameAttribute(){
        if(!empty($this->category_id) && $this->relationLoaded('category')){
            return $this->category->name ?? null;
        }
        if(!empty($this->category_id)){
            return $this->category()->value('name');
        }
        return null;
    }

    public function category(){
        return $this->hasOne(ArticleCategoryModel::class,'id','category_id');
    }
}
