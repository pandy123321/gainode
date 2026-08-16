<?php

namespace library\dict;


class ErrorDict
{
    const LoginVerifyError = 4000;
    const TokenVerificationFailed = 4001;
    const RequestFormatError = 4002;
    const InterfaceAuthenticationFailed = 4003;
    const InternalServerError = 5001;
    const TooManyRequests = 5002;
    const ParameterInformationError = 5003;
    const SignatureInformationError = 5004;
    const ParameterSignatureError = 5005;
    const CacheSettingFailed = 5006;
    const CacheDeletionFailed = 5007;
    const CacheDoesNotExist = 5008;
    const PleaseDoNotResubmit = 5009;
    const EncryptionFailed = 5010;
    const DecryptionFailed = 5011;
    const CurrentlyHasNoAccessPermission = 5012;
    const RedisConnectionFailed = 5013;
    const MySQLConnectionFailed = 5014;
    const FailedToWriteConfigurationFile = 5015;
    const FailedToSendEmail = 5016;
    const SQLExecutionFailed = 5017;
    const SocketNotConnected = 5018;
    const SocketMessageSendingFailed = 5019;

    // =========================================================================
    // V2.0 统一错误分类（05 §7，16 项）— 字符串错误码 + HTTP 状态映射
    // =========================================================================
    const VALIDATION_ERROR = 'VALIDATION_ERROR';
    const AUTH_UNAUTHENTICATED = 'AUTH_UNAUTHENTICATED';
    const AUTH_FORBIDDEN = 'AUTH_FORBIDDEN';
    const KYC_REQUIRED = 'KYC_REQUIRED';
    const POLICY_DENIED = 'POLICY_DENIED';
    const FEATURE_CLOSED = 'FEATURE_CLOSED';
    const CONSENT_VERSION_MISMATCH = 'CONSENT_VERSION_MISMATCH';
    const IDEMPOTENCY_CONFLICT = 'IDEMPOTENCY_CONFLICT';
    const OBJECT_VERSION_CONFLICT = 'OBJECT_VERSION_CONFLICT';
    const QUOTE_EXPIRED = 'QUOTE_EXPIRED';
    const INSUFFICIENT_APT = 'INSUFFICIENT_APT';
    const INSUFFICIENT_POWER = 'INSUFFICIENT_POWER';
    const MARKET_LOCKED = 'MARKET_LOCKED';
    const DEPENDENCY_UNAVAILABLE = 'DEPENDENCY_UNAVAILABLE';
    const RESULT_UNKNOWN = 'RESULT_UNKNOWN';
    const INTERNAL_ERROR = 'INTERNAL_ERROR';

    /**
     * 05 §7 错误码 → HTTP 状态码映射（V2.0 统一 envelope）。
     * RESULT_UNKNOWN 返回 202：客户端须用原 Idempotency-Key 查询原请求结果，而非重试创建。
     *
     * @param string $code
     * @return int
     */
    public static function httpStatus(string $code): int
    {
        $map = [
            self::VALIDATION_ERROR          => 400,
            self::AUTH_UNAUTHENTICATED      => 401,
            self::AUTH_FORBIDDEN            => 403,
            self::KYC_REQUIRED              => 403,
            self::POLICY_DENIED             => 403,
            self::FEATURE_CLOSED            => 403,
            self::CONSENT_VERSION_MISMATCH  => 409,
            self::IDEMPOTENCY_CONFLICT      => 409,
            self::OBJECT_VERSION_CONFLICT   => 409,
            self::QUOTE_EXPIRED             => 409,
            self::INSUFFICIENT_APT          => 422,
            self::INSUFFICIENT_POWER        => 422,
            self::MARKET_LOCKED             => 422,
            self::DEPENDENCY_UNAVAILABLE    => 503,
            self::RESULT_UNKNOWN            => 202,
            self::INTERNAL_ERROR            => 500,
        ];
        return $map[$code] ?? 500;
    }


    public function getMessage($code)
    {
        $data = [
            self::LoginVerifyError => '账户名或密码错误',
            self::TokenVerificationFailed => 'Token验证失败',
            self::RequestFormatError => '请求格式错误',
            self::InterfaceAuthenticationFailed=> '接口鉴权失败',
            self::InternalServerError => '内部服务器错误',
            self::TooManyRequests => '请求过多',
            self::ParameterInformationError => '参数信息错误',
            self::SignatureInformationError => '签名信息错误',
            self::ParameterSignatureError => '参数签名错误',
            self::CacheSettingFailed => '设置缓存失败',
            self::CacheDeletionFailed => '删除缓存失败',
            self::CacheDoesNotExist => '缓存不存在',
            self::PleaseDoNotResubmit => '请勿重复提交',
            self::EncryptionFailed => '加密失败',
            self::DecryptionFailed => '解密失败',
            self::CurrentlyHasNoAccessPermission => '暂无访问权限',
            self::RedisConnectionFailed => 'Redis连接失败',
            self::MySQLConnectionFailed => 'Mysql连接失败',
            self::FailedToWriteConfigurationFile => '写入配置文件失败',
            self::FailedToSendEmail => '发送邮件失败',
            self::SQLExecutionFailed => 'Sql执行失败',
            self::SocketNotConnected => 'Socket未连接',
            self::SocketMessageSendingFailed => 'Socket消息发送失败',
        ];
        return $data[$code] ?? '未知错误';
    }
}
