<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiExceptionHandler;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Ghi log request + response cho mọi API (/api/*) để debug.
 * Logging luôn fail-safe: lỗi ghi log KHÔNG được làm API thành 500.
 * Xem: storage/logs/api-YYYY-MM-DD.log
 */
class LogApiRequestResponse
{
    /** Độ dài tối đa body response ghi vào log (tránh file phình). */
    private const MAX_RESPONSE_CHARS = 8000;

    /** Các key nhạy cảm sẽ bị che khi log. */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'access_token',
        'refresh_token',
        'authorization',
        'secret',
        'api_key',
        'apiKey',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $requestId = (string) Str::uuid();
        $startedAt = microtime(true);

        $request->attributes->set('api_log_request_id', $requestId);

        $this->safeLog('info', 'API Request', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'path' => '/'.$request->path(),
            'route' => $request->route()?->getName() ?? $request->route()?->uri(),
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
            'query' => $this->redact($request->query()),
            'body' => $this->redact($this->requestBody($request)),
            'headers' => $this->safeHeaders($request),
        ]);

        try {
            /** @var Response $response */
            $response = $next($request);
        } catch (Throwable $e) {
            // Bắt exception để vẫn trả JSON + biết lỗi ở đâu
            report($e);

            try {
                $handled = ApiExceptionHandler::render($e, $request);
            } catch (Throwable $renderError) {
                report($renderError);
                $handled = response()->json([
                    'message' => config('app.debug')
                        ? $e->getMessage()
                        : 'Lỗi máy chủ. Vui lòng thử lại sau.',
                    'request_id' => $requestId,
                ], 500);
            }

            if ($handled === null) {
                throw $e;
            }

            $response = $handled;
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $status = $response->getStatusCode();
        $level = $status >= 500 ? 'error' : ($status >= 400 ? 'warning' : 'info');

        $this->safeLog($level, 'API Response', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => '/'.$request->path(),
            'status' => $status,
            'duration_ms' => $durationMs,
            'user_id' => $request->user()?->id,
            'body' => $this->responseBody($response),
        ]);

        return $response;
    }

    /**
     * Ghi log an toàn — không bao giờ ném exception ra ngoài.
     *
     * @param  array<string, mixed>  $context
     */
    private function safeLog(string $level, string $message, array $context = []): void
    {
        try {
            if (array_key_exists('api', config('logging.channels', []))) {
                Log::channel('api')->{$level}($message, $context);
            } else {
                Log::{$level}('[api-fallback] '.$message, $context);
            }
        } catch (Throwable $e) {
            // Cuối cùng cố ghi vào error_log PHP để vẫn debug được trên prod
            try {
                Log::{$level}('[api-fallback] '.$message, $context);
            } catch (Throwable) {
                error_log('[API LOG FAILED] '.$message.' | '.$e->getMessage());
            }
        }
    }

    /**
     * @return array<string, mixed>|string|null
     */
    private function requestBody(Request $request): array|string|null
    {
        try {
            if ($request->isJson()) {
                return $request->json()->all();
            }

            $input = $request->except(['file', 'files', 'hinh_anh', 'logo', 'anh']);

            if ($input !== []) {
                return $input;
            }

            $raw = $request->getContent();

            return $raw !== '' ? $raw : null;
        } catch (Throwable) {
            return '[unreadable body]';
        }
    }

    /**
     * @param  array<string, mixed>|string|null  $data
     * @return array<string, mixed>|string|null
     */
    private function redact(array|string|null $data): array|string|null
    {
        if (! is_array($data)) {
            return $data;
        }

        $out = [];
        foreach ($data as $key => $value) {
            $keyStr = (string) $key;
            if ($this->isSensitiveKey($keyStr)) {
                $out[$key] = '***REDACTED***';
                continue;
            }

            $out[$key] = is_array($value) ? $this->redact($value) : $value;
        }

        return $out;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);

        foreach (self::SENSITIVE_KEYS as $sensitive) {
            if ($normalized === strtolower($sensitive) || str_contains($normalized, strtolower($sensitive))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    private function safeHeaders(Request $request): array
    {
        $headers = [];
        foreach (['accept', 'content-type', 'x-requested-with', 'origin', 'referer', 'user-agent'] as $name) {
            $value = $request->headers->get($name);
            if ($value !== null && $value !== '') {
                $headers[$name] = $value;
            }
        }

        if ($request->bearerToken()) {
            $headers['authorization'] = 'Bearer ***REDACTED***';
        }

        return $headers;
    }

    private function responseBody(Response $response): mixed
    {
        try {
            $contentType = (string) $response->headers->get('Content-Type', '');

            // Bỏ qua binary / file download
            if ($contentType !== '' && ! str_contains($contentType, 'json') && ! str_contains($contentType, 'text/')) {
                return '[non-text content: '.$contentType.']';
            }

            $content = $response->getContent();
            if ($content === false || $content === '') {
                return null;
            }

            $decoded = json_decode($content, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $redacted = $this->redact($decoded);
                $encoded = json_encode($redacted, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                if ($encoded !== false && strlen($encoded) > self::MAX_RESPONSE_CHARS) {
                    return substr($encoded, 0, self::MAX_RESPONSE_CHARS).'...[truncated]';
                }

                return $redacted;
            }

            if (strlen($content) > self::MAX_RESPONSE_CHARS) {
                return substr($content, 0, self::MAX_RESPONSE_CHARS).'...[truncated]';
            }

            return $content;
        } catch (Throwable) {
            return '[unreadable response]';
        }
    }
}
