<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

/**
 * Chuẩn hoá mọi exception trên API thành JSON cho FE + ghi log vị trí lỗi.
 */
class ApiExceptionHandler
{
    public static function wantsApi(Request $request): bool
    {
        return $request->is('api/*') || $request->expectsJson();
    }

    public static function render(Throwable $e, Request $request): ?JsonResponse
    {
        if (! self::wantsApi($request)) {
            return null;
        }

        $response = match (true) {
            $e instanceof ValidationException => self::validation($e, $request),
            $e instanceof AuthenticationException => self::json(
                'Token không hợp lệ hoặc đã hết hạn. Vui lòng đăng nhập lại.',
                401,
                request: $request
            ),
            $e instanceof AuthorizationException,
            $e instanceof AccessDeniedHttpException => self::json(
                $e->getMessage() !== '' ? $e->getMessage() : 'Bạn không có quyền thực hiện thao tác này.',
                403,
                request: $request
            ),
            $e instanceof ModelNotFoundException => self::json(
                self::modelNotFoundMessage($e),
                404,
                request: $request
            ),
            $e instanceof NotFoundHttpException => self::json(
                self::notFoundMessage($e),
                404,
                request: $request
            ),
            $e instanceof MethodNotAllowedHttpException => self::json(
                'Phương thức HTTP không được hỗ trợ cho endpoint này.',
                405,
                request: $request
            ),
            $e instanceof TooManyRequestsHttpException => self::json(
                'Quá nhiều yêu cầu. Vui lòng thử lại sau.',
                429,
                ['retry_after' => $e->getHeaders()['Retry-After'] ?? null],
                $request
            ),
            $e instanceof BadRequestException => self::json(
                $e->getMessage() !== '' ? $e->getMessage() : 'Yêu cầu không hợp lệ.',
                400,
                request: $request
            ),
            $e instanceof HttpExceptionInterface => self::httpException($e, $request),
            $e instanceof QueryException => self::serverError($e, $request, 'Lỗi truy vấn dữ liệu. Vui lòng thử lại sau.'),
            default => self::serverError($e, $request, 'Lỗi máy chủ. Vui lòng thử lại sau.'),
        };

        self::logException($e, $request, $response->getStatusCode());

        return $response;
    }

    /**
     * Ghi chi tiết exception vào channel api (file, line, stack) để biết lỗi ở đâu.
     */
    public static function logException(Throwable $e, Request $request, ?int $status = null): void
    {
        $status ??= self::guessStatus($e);
        $context = self::exceptionContext($e, $request, $status);

        // 5xx / lỗi không mong đợi → error; còn lại → warning (vẫn thấy trong api.log)
        if ($status >= 500) {
            Log::channel('api')->error('API Exception', $context);
        } elseif ($status >= 400 && ! $e instanceof ValidationException) {
            Log::channel('api')->warning('API Exception', $context);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public static function exceptionContext(Throwable $e, ?Request $request = null, ?int $status = null): array
    {
        $request ??= request();

        return [
            'request_id' => $request?->attributes->get('api_log_request_id'),
            'method' => $request?->method(),
            'path' => $request ? '/'.$request->path() : null,
            'user_id' => $request?->user()?->id,
            'status' => $status,
            'exception' => $e::class,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'code' => $e->getCode(),
            'trace' => collect($e->getTrace())
                ->take(15)
                ->map(fn (array $frame) => [
                    'file' => $frame['file'] ?? null,
                    'line' => $frame['line'] ?? null,
                    'function' => ($frame['class'] ?? '').($frame['type'] ?? '').($frame['function'] ?? ''),
                ])
                ->all(),
            'previous' => $e->getPrevious() ? [
                'exception' => $e->getPrevious()::class,
                'message' => $e->getPrevious()->getMessage(),
                'file' => $e->getPrevious()->getFile(),
                'line' => $e->getPrevious()->getLine(),
            ] : null,
        ];
    }

    private static function guessStatus(Throwable $e): int
    {
        return match (true) {
            $e instanceof ValidationException => $e->status,
            $e instanceof AuthenticationException => 401,
            $e instanceof AuthorizationException,
            $e instanceof AccessDeniedHttpException => 403,
            $e instanceof ModelNotFoundException,
            $e instanceof NotFoundHttpException => 404,
            $e instanceof MethodNotAllowedHttpException => 405,
            $e instanceof TooManyRequestsHttpException => 429,
            $e instanceof BadRequestException => 400,
            $e instanceof HttpExceptionInterface => $e->getStatusCode(),
            default => 500,
        };
    }

    private static function validation(ValidationException $e, Request $request): JsonResponse
    {
        $message = $e->getMessage();
        if ($message === '' || $message === 'The given data was invalid.') {
            $message = 'Dữ liệu không hợp lệ.';
        }

        $payload = [
            'message' => $message,
            'errors' => $e->errors(),
        ];

        if ($requestId = $request->attributes->get('api_log_request_id')) {
            $payload['request_id'] = $requestId;
        }

        return response()->json($payload, $e->status);
    }

    private static function httpException(HttpExceptionInterface $e, Request $request): JsonResponse
    {
        $status = $e->getStatusCode();
        $message = $e->getMessage();

        if ($message === '') {
            $message = self::defaultMessage($status);
        }

        return self::json($message, $status, request: $request);
    }

    private static function serverError(Throwable $e, Request $request, string $fallbackMessage): JsonResponse
    {
        $payload = [
            'message' => config('app.debug') ? ($e->getMessage() ?: $fallbackMessage) : $fallbackMessage,
        ];

        if ($requestId = $request->attributes->get('api_log_request_id')) {
            $payload['request_id'] = $requestId;
        }

        if (config('app.debug')) {
            $payload['exception'] = class_basename($e);
            $payload['file'] = $e->getFile();
            $payload['line'] = $e->getLine();
            $payload['trace'] = collect($e->getTrace())
                ->take(8)
                ->map(function (array $frame) {
                    $file = $frame['file'] ?? '[internal]';
                    $line = $frame['line'] ?? '?';
                    $fn = ($frame['class'] ?? '').($frame['type'] ?? '').($frame['function'] ?? '');

                    return "{$file}:{$line} {$fn}";
                })
                ->values()
                ->all();
        }

        return response()->json($payload, 500);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private static function json(string $message, int $status, array $extra = [], ?Request $request = null): JsonResponse
    {
        $payload = array_filter(
            ['message' => $message, ...$extra],
            fn ($value) => $value !== null && $value !== ''
        );

        $requestId = $request?->attributes->get('api_log_request_id');
        if ($requestId) {
            $payload['request_id'] = $requestId;
        }

        return response()->json($payload, $status);
    }

    private static function modelNotFoundMessage(ModelNotFoundException $e): string
    {
        $model = class_basename($e->getModel() ?: 'Resource');

        return "Không tìm thấy {$model}.";
    }

    private static function notFoundMessage(NotFoundHttpException $e): string
    {
        $message = $e->getMessage();

        if ($message === ''
            || str_contains($message, 'No query results')
            || str_contains($message, 'could not be found')
            || str_contains($message, 'Not Found')
        ) {
            return 'Không tìm thấy tài nguyên.';
        }

        return $message;
    }

    private static function defaultMessage(int $status): string
    {
        return match ($status) {
            400 => 'Yêu cầu không hợp lệ.',
            401 => 'Chưa xác thực.',
            403 => 'Bạn không có quyền thực hiện thao tác này.',
            404 => 'Không tìm thấy tài nguyên.',
            405 => 'Phương thức HTTP không được hỗ trợ.',
            408 => 'Hết thời gian chờ yêu cầu.',
            409 => 'Xung đột dữ liệu.',
            419 => 'Phiên làm việc đã hết hạn.',
            422 => 'Dữ liệu không hợp lệ.',
            429 => 'Quá nhiều yêu cầu. Vui lòng thử lại sau.',
            503 => 'Dịch vụ tạm thời không khả dụng.',
            default => $status >= 500
                ? 'Lỗi máy chủ. Vui lòng thử lại sau.'
                : 'Đã xảy ra lỗi.',
        };
    }
}
