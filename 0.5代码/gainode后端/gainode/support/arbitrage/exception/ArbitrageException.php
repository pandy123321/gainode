<?php
declare(strict_types=1);

namespace support\arbitrage\exception;

/** 套利库统一异常：携带机器可读 errorCode 与上下文，便于上层按 code 处理。 */
final class ArbitrageException extends \RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message, public readonly array $context = [])
    {
        parent::__construct($message);
    }
}
