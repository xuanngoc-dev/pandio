<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

/**
 * Chuẩn hoá mọi exception trên API thành JSON cho FE.
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

        return match (true) {
            $e instanceof ValidationException => self::validation($e),
            $e instanceof AuthenticationException => self::json(
                'Token không hợp lệ hoặc đã hết hạn. Vui lòng đăng nhập lại.',
                401
            ),
            $e instanceof AuthorizationException,
            $e instanceof AccessDeniedHttpException => self::json(
                $e->getMessage() !== '' ? $e->getMessage() : 'Bạn không có quyền thực hiện thao tác này.',
                403
            ),
            $e instanceof ModelNotFoundException => self::json(
                self::modelNotFoundMessage($e),
                404
            ),
            $e instanceof NotFoundHttpException => self::json(
                self::notFoundMessage($e),
                404
            ),
            $e instanceof MethodNotAllowedHttpException => self::json(
                'Phương thức HTTP không được hỗ trợ cho endpoint này.',
                405
            ),
            $e instanceof TooManyRequestsHttpException => self::json(
                'Quá nhiều yêu cầu. Vui lòng thử lại sau.',
                429,
                ['retry_after' => $e->getHeaders()['Retry-After'] ?? null]
            ),
            $e instanceof BadRequestException => self::json(
                $e->getMessage() !== '' ? $e->getMessage() : 'Yêu cầu không hợp lệ.',
                400
            ),
            $e instanceof HttpExceptionInterface => self::httpException($e),
            $e instanceof QueryException => self::serverError($e, 'Lỗi truy vấn dữ liệu. Vui lòng thử lại sau.'),
            default => self::serverError($e, 'Lỗi máy chủ. Vui lòng thử lại sau.'),
        };
    }

    private static function validation(ValidationException $e): JsonResponse
    {
        $message = $e->getMessage();
        if ($message === '' || $message === 'The given data was invalid.') {
            $message = 'Dữ liệu không hợp lệ.';
        }

        return response()->json([
            'message' => $message,
            'errors' => $e->errors(),
        ], $e->status);
    }

    private static function httpException(HttpExceptionInterface $e): JsonResponse
    {
        $status = $e->getStatusCode();
        $message = $e->getMessage();

        if ($message === '') {
            $message = self::defaultMessage($status);
        }

        return self::json($message, $status);
    }

    private static function serverError(Throwable $e, string $fallbackMessage): JsonResponse
    {
        $payload = [
            'message' => config('app.debug') ? ($e->getMessage() ?: $fallbackMessage) : $fallbackMessage,
        ];

        if (config('app.debug')) {
            $payload['exception'] = class_basename($e);
            $payload['file'] = $e->getFile();
            $payload['line'] = $e->getLine();
        }

        return response()->json($payload, 500);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private static function json(string $message, int $status, array $extra = []): JsonResponse
    {
        $payload = array_filter(
            ['message' => $message, ...$extra],
            fn ($value) => $value !== null && $value !== ''
        );

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
