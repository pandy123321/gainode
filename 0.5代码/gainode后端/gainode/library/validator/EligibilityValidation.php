<?php

declare(strict_types=1);

namespace library\validator;

use support\extend\Validator;

/**
 * Eligibility 校验（S02-P02 子流程 5）。
 *
 * eligibility / login-audit 均为 GET 只读，无请求体字段；保留空场景以对齐
 * 交付清单，实际校验交由路径级鉴权（bearerAuth）与投影 fail-closed。
 */
class EligibilityValidation extends Validator
{
    public $rules = [];

    protected $attributes = [];

    protected $scenes = [];
}
