<?php

declare(strict_types=1);

namespace support\exception;

use library\dict\ErrorDict;
use RuntimeException;

/**
 * V2 领域异常：携带 05 §7 字符串错误码 + HTTP 状态映射。
 *
 * 用于 S02-P02 起的领域/应用服务 fail-closed 与状态机守卫：
 * 抛错时携带 result_code，控制器统一捕获并映射为 Envelope::error()。
 * 区别于 V1.x VerifyException（数值码），本异常用 05 §7 字符串码。
 */
class DomainException extends RuntimeException
{
    private string $resultCode;

    public function __construct(string $resultCode, string $message = '', ?\Throwable $previous = null)
    {
        $this->resultCode = $resultCode;
        parent::__construct($message !== '' ? $message : $resultCode, 0, $previous);
    }

    public function resultCode(): string
    {
        return $this->resultCode;
    }

    public function httpStatus(): int
    {
        return ErrorDict::httpStatus($this->resultCode);
    }
}
