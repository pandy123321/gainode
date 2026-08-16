<?php

namespace library\model\module;

use support\extend\Model;

/**property*/
class TemplateModel extends Model
{
    public $table = '{table}';
    public $primaryKey = '{pk}';
    public $connection = '{adapter}';
    const UPDATED_AT = '{updated_at}';
    public $fields=["fields"];
}
