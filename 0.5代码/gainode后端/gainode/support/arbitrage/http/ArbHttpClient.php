<?php
declare(strict_types=1);

namespace support\arbitrage\http;

use support\arbitrage\exception\ArbitrageException;

/** 零依赖 HTTP 客户端：curl 优先、stream 兜底，含指数退避重试与限流/5xx 区分。 */
final class ArbHttpClient
{
    public function __construct(private int $maxAttempts = 3) {}

    /** @param array<string,string|int|float|bool> $form @param array<string,string|int> $headers */
    public function postForm(string $url, array $form, array $headers = [], int $timeoutSeconds = 15): array
    {
        return $this->request('POST', $url, $headers + ['Content-Type' => 'application/x-www-form-urlencoded'], http_build_query($form), $timeoutSeconds);
    }

    /** @param array<string,string|int> $headers */
    public function get(string $url, array $headers = [], int $timeoutSeconds = 20): array
    {
        return $this->request('GET', $url, $headers, null, $timeoutSeconds);
    }

    /** @param array<string,string|int> $headers @return array{status:int,body:string,headers:list<string>} */
    private function request(string $method, string $url, array $headers, ?string $body, int $timeoutSeconds): array
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new ArbitrageException('INVALID_URL', 'Invalid HTTP URL', ['url' => $url]);
        }

        $attempt = 0;
        $lastStatus = 0;
        $lastMessage = '';
        do {
            $attempt++;
            try {
                $result = function_exists('curl_init')
                    ? $this->curlRequest($method, $url, $headers, $body, $timeoutSeconds)
                    : $this->streamRequest($method, $url, $headers, $body, $timeoutSeconds);
                $lastStatus = $result['status'];
                if ($result['status'] !== 429 && $result['status'] < 500) {
                    return $result;
                }
                $lastMessage = 'HTTP '.$result['status'];
            } catch (\Throwable $e) {
                $lastMessage = $e->getMessage();
            }
            if ($attempt < $this->maxAttempts) {
                usleep((int) (100000 * (2 ** ($attempt - 1))));
            }
        } while ($attempt < $this->maxAttempts);

        $code = $lastStatus === 429 ? 'THIRD_PARTY_RATE_LIMITED' : ($lastStatus >= 500 ? 'THIRD_PARTY_5XX' : 'THIRD_PARTY_UNAVAILABLE');
        throw new ArbitrageException($code, $lastMessage !== '' ? $lastMessage : 'HTTP request failed', ['status' => $lastStatus]);
    }

    /** @param array<string,string|int> $headers @return array{status:int,body:string,headers:list<string>} */
    private function curlRequest(string $method, string $url, array $headers, ?string $body, int $timeoutSeconds): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('Unable to initialize cURL');
        }
        $responseHeaders = [];
        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name.': '.$value;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => min(10, $timeoutSeconds),
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$responseHeaders): int {
                $responseHeaders[] = rtrim($line, "\r\n");
                return strlen($line);
            },
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $bodyText = curl_exec($ch);
        if ($bodyText === false) {
            $message = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException($message !== '' ? $message : 'cURL request failed');
        }
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return ['status' => $status, 'body' => (string) $bodyText, 'headers' => array_values(array_filter($responseHeaders, static fn(string $v): bool => $v !== ''))];
    }

    /** @param array<string,string|int> $headers @return array{status:int,body:string,headers:list<string>} */
    private function streamRequest(string $method, string $url, array $headers, ?string $body, int $timeoutSeconds): array
    {
        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name.': '.$value;
        }
        $context = stream_context_create(['http' => [
            'method' => $method,
            'header' => implode("\r\n", $headerLines),
            'content' => $body ?? '',
            'timeout' => $timeoutSeconds,
            'ignore_errors' => true,
        ]]);
        $response = @file_get_contents($url, false, $context);
        $responseHeaders = $http_response_header ?? [];
        $status = 0;
        if (isset($responseHeaders[0]) && preg_match('/\\s(\\d{3})\\s/', $responseHeaders[0], $m)) {
            $status = (int) $m[1];
        }
        if ($response === false) {
            throw new \RuntimeException('HTTP stream request failed');
        }
        return ['status' => $status, 'body' => $response, 'headers' => array_values($responseHeaders)];
    }
}
