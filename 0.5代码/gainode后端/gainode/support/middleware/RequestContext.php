<?php

declare(strict_types=1);

namespace support\middleware;

use library\dict\ErrorDict;
use library\response\Envelope;
use support\Middleware;
use Webman\Http\Request;
use Webman\Http\Response;
use Workerman\Coroutine\Context;

/**
 * 统一 RequestContext 中间件（07 §S02-P01 步骤 4）。
 *
 * 职责：
 *   1. 解析六请求头并写入协程上下文（Context）。
 *   2. 透传 / 生成 X-Request-Id（缺失或超长时服务端生成 32 位 hex）。
 *   3. 写操作强制 Idempotency-Key（缺失/超长 → 400 fail-closed）。
 *
 * 六请求头（Environment Freeze 候选，见 TASK-20260816-008/design.md §2）：
 *   Authorization / Idempotency-Key / If-Match / Accept-Language / X-Request-Id / X-Timestamp
 */
class RequestContext extends Middleware
{
    public const HEADER_AUTHORIZATION = 'Authorization';
    public const HEADER_IDEMPOTENCY_KEY = 'Idempotency-Key';
    public const HEADER_IF_MATCH = 'If-Match';
    public const HEADER_ACCEPT_LANGUAGE = 'Accept-Language';
    public const HEADER_X_REQUEST_ID = 'X-Request-Id';
    public const HEADER_X_TIMESTAMP = 'X-Timestamp';

    private const WRITE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];
    private const MAX_KEY_LENGTH = 64;

    public function process(Request $request, callable $next): Response
    {
        $requestId = $this->resolveRequestId($request);
        Context::set('request_id', $requestId);
        Context::set('request_context', $this->buildContext($request, $requestId));

        if (in_array($request->method(), self::WRITE_METHODS, true)
            && (bool) env('API_ENFORCE_IDEMPOTENCY', true)) {
            $idempotencyKey = $request->header(self::HEADER_IDEMPOTENCY_KEY, '');
            if ($idempotencyKey === '' || strlen($idempotencyKey) > self::MAX_KEY_LENGTH) {
                return $this->failClosed(
                    ErrorDict::VALIDATION_ERROR,
                    '写操作必须携带 Idempotency-Key（1~64 字符）',
                    400,
                    $requestId
                );
            }
        }

        return $next($request);
    }

    /**
     * 获取当前请求 request_id（协程上下文）。
     */
    public static function getRequestId(): string
    {
        return Context::get('request_id', '');
    }

    /**
     * 获取当前请求上下文（六请求头解析结果）。
     */
    public static function getContext(): array
    {
        return Context::get('request_context', []);
    }

    private function resolveRequestId(Request $request): string
    {
        $requestId = $request->header(self::HEADER_X_REQUEST_ID, '');
        if ($requestId === '' || strlen($requestId) > self::MAX_KEY_LENGTH) {
            return bin2hex(random_bytes(16));
        }
        return $requestId;
    }

    private function buildContext(Request $request, string $requestId): array
    {
        return [
            'request_id'      => $requestId,
            'authorization'   => $request->header(self::HEADER_AUTHORIZATION, ''),
            'idempotency_key' => $request->header(self::HEADER_IDEMPOTENCY_KEY, ''),
            'if_match'        => $request->header(self::HEADER_IF_MATCH, ''),
            'accept_language' => $request->header(self::HEADER_ACCEPT_LANGUAGE, 'zh-CN'),
            'x_timestamp'     => $request->header(self::HEADER_X_TIMESTAMP, ''),
            'method'          => $request->method(),
        ];
    }

    private function failClosed(string $code, string $message, int $status, string $requestId): Response
    {
        $body = json_encode(Envelope::error($code, $message, $status, [], $requestId), JSON_UNESCAPED_UNICODE);
        return response($body, $status, ['Content-Type' => 'application/json']);
    }
}
