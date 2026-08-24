<?php

declare(strict_types=1);

namespace support\controller;

use library\dict\ErrorDict;
use library\dict\SecurityReasonMap;
use library\response\Envelope;
use support\exception\DomainException;
use support\extend\Validator;
use support\middleware\RequestContext;
use support\Response;

/**
 * V2 API 控制器基类（S02-P02 起使用）。
 *
 * 统一 05 §1/§10 envelope 输出，并将 DomainException 映射为
 * Envelope::error()（携带 result_code / http_status）。
 * 校验通过显式调用 validate()（不走 V1.x beforeAction 的 failJson 格式）。
 */
abstract class ApiV2 extends Api
{
    /**
     * 场景校验（Laravel 规则）。失败抛出 VALIDATION_ERROR。
     */
    protected function validate(string $scene, array $data, ?Validator $validator = null): void
    {
        if ($validator === null) {
            return;
        }
        if (!$validator->verifyRequestData($scene, $data)) {
            $msg = $validator->getMessage();
            if (is_array($msg)) {
                $first = reset($msg);
                $msg = is_array($first) ? (string) reset($first) : (string) $first;
            }
            throw new DomainException(ErrorDict::VALIDATION_ERROR, (string) $msg);
        }
    }

    /**
     * 成功响应（Envelope::success）。
     */
    protected function envelope($data = [], array $meta = [], array $extra = []): Response
    {
        $requestId = RequestContext::getRequestId();
        $body = json_encode(Envelope::success($data, $meta, $extra, $requestId), JSON_UNESCAPED_UNICODE);
        return \response($body, 200, ['Content-Type' => 'application/json']);
    }

    /**
     * 错误响应（Envelope::error）。DomainException 携带 result_code + http_status。
     */
    protected function envelopeError(\Throwable $e): Response
    {
        // BE-11：AuthorizeException 已继承 DomainException（携带 AUTH_UNAUTHENTICATED/401），
        // 由首分支统一映射；此处不再需要专用 elseif。
        if ($e instanceof DomainException) {
            $code = $e->resultCode();
            $status = $e->httpStatus();
            $msg = $e->getMessage() !== '' ? $e->getMessage() : trans(SecurityReasonMap::DEFAULT_KEY);
        } else {
            $code = ErrorDict::INTERNAL_ERROR;
            $status = 500;
            $msg = trans(SecurityReasonMap::DEFAULT_KEY);
        }
        $requestId = RequestContext::getRequestId();
        $body = json_encode(Envelope::error($code, $msg, $status, [], $requestId), JSON_UNESCAPED_UNICODE);
        return \response($body, $status, ['Content-Type' => 'application/json']);
    }
}
