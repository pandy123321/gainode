<?php

declare(strict_types=1);

namespace library\validator;

use support\extend\Validator;

/**
 * KYC 场景校验（S02-P02 子流程 4）。
 *
 * 附件走后端签发对象引用（attachment_refs），不接收用户上传直链明文。
 */
class KycValidation extends Validator
{
    public $rules = [
        'kyc_level'       => 'required|string',
        'attachment_refs' => 'required|array|min:1',
        'consent_version' => 'nullable|string',
    ];

    protected $attributes = [
        'kyc_level'       => 'KYC 等级',
        'attachment_refs' => '附件对象引用',
        'consent_version' => '授权版本',
    ];

    protected $scenes = [
        'submit' => ['kyc_level', 'attachment_refs', 'consent_version'],
    ];
}
