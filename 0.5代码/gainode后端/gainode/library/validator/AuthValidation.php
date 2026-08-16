<?php

declare(strict_types=1);

namespace library\validator;

use support\extend\Validator;

/**
 * Auth 场景校验（S02-P02 子流程 1/2/3）。
 *
 * 规则对齐 openapi/auth.yaml 各 request schema。账户枚举防护在 Application
 * Service 层通过 SecurityReasonMap 统一文案，不在校验层暴露存在性。
 */
class AuthValidation extends Validator
{
    public $rules = [
        'account'         => 'required|string',
        'account_type'    => 'required|string|in:email,mobile',
        'password'        => 'required|string|min:8|max:128',
        'vcode'           => 'required|string',
        'type'            => 'string|in:email,mobile',
        'source'          => 'string|in:login,register,forget,code',
        'invite_code'     => 'nullable|string',
        'nickname'        => 'nullable|string',
        'consent_version' => 'required|string',
        'code'            => 'required|string',
        'session_id'      => 'nullable|string',
        'refresh_token'   => 'required|string',
    ];

    protected $attributes = [
        'account'         => '账号',
        'account_type'    => '账号类型',
        'password'        => '密码',
        'vcode'           => '验证码',
        'type'            => '类型',
        'source'          => '来源',
        'invite_code'     => '邀请码',
        'nickname'        => '昵称',
        'consent_version' => '授权版本',
        'code'            => '验证码',
        'session_id'      => '会话ID',
        'refresh_token'   => '刷新令牌',
    ];

    protected $scenes = [
        'register'      => ['account', 'account_type', 'password', 'vcode', 'invite_code', 'nickname', 'consent_version'],
        'login'         => ['account', 'password'],
        'otpVerify'     => ['account', 'vcode', 'type', 'source'],
        'otpResend'     => ['account', 'type', 'source'],
        'mfaVerify'     => ['code', 'session_id'],
        'refresh'       => ['refresh_token'],
        'recovery'      => ['account'],
        'passwordReset' => ['account', 'vcode', 'password'],
    ];
}
