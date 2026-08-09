<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Exceptions\ApiExceptionHandler;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Bọc logic từng API bằng try/catch, trả JSON thống nhất cho FE.
 */
trait HandlesApiExceptions
{
    /**
     * @param  callable(): mixed  $callback
     */
    protected function handleApi(callable $callback, string $action = 'thao tác'): JsonResponse
    {
        try {
            $result = $callback();

            if ($result instanceof JsonResponse) {
                return $result;
            }

            return response()->json($result);
        } catch (ValidationException|AuthenticationException|AuthorizationException|HttpExceptionInterface $e) {
            // Giữ nguyên để ApiExceptionHandler / Laravel format chuẩn (422, 401, 403, abort...)
            throw $e;
        } catch (ModelNotFoundException $e) {
            $this->logHandledException($e, $action, 404);

            return $this->errorJson(
                "Không tìm thấy dữ liệu khi {$action}.",
                404
            );
        } catch (QueryException $e) {
            $status = $this->mapQueryExceptionStatus($e);
            $this->logHandledException($e, $action, $status);

            return $this->errorJson(
                $this->mapQueryExceptionMessage($e, $action),
                $status,
                $e
            );
        } catch (Throwable $e) {
            $this->logHandledException($e, $action, 500);

            return $this->errorJson(
                config('app.debug')
                    ? ($e->getMessage() !== '' ? $e->getMessage() : "Đã xảy ra lỗi khi {$action}.")
                    : "Đã xảy ra lỗi khi {$action}. Vui lòng thử lại sau.",
                500,
                $e
            );
        }
    }

    protected function logHandledException(Throwable $e, string $action, int $status): void
    {
        // report() → laravel.log; channel api → api-YYYY-MM-DD.log
        report($e);

        $context = ApiExceptionHandler::exceptionContext($e, request(), $status);
        $context['action'] = $action;
        $context['controller'] = static::class;

        if ($status >= 500) {
            Log::channel('api')->error('API handleApi Exception', $context);
        } else {
            Log::channel('api')->warning('API handleApi Exception', $context);
        }
    }

    protected function errorJson(string $message, int $status, ?Throwable $e = null): JsonResponse
    {
        $payload = ['message' => $message];

        if ($requestId = request()->attributes->get('api_log_request_id')) {
            $payload['request_id'] = $requestId;
        }

        if ($e !== null && config('app.debug') && $status >= 500) {
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

        return response()->json($payload, $status);
    }

    protected function mapQueryExceptionStatus(QueryException $e): int
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        // Duplicate / conflict
        if ($sqlState === '23000' || in_array($driverCode, [1062, 1451, 1452], true)) {
            return $driverCode === 1452 ? 422 : 409;
        }

        return 500;
    }

    protected function mapQueryExceptionMessage(QueryException $e, string $action): string
    {
        if (config('app.debug')) {
            return $e->getMessage();
        }

        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $driverCode = (int) ($e->errorInfo[1] ?? 0);

        if ($sqlState === '23000' || $driverCode === 1062) {
            return "Dữ liệu bị trùng khi {$action}.";
        }

        if ($driverCode === 1451) {
            return "Không thể {$action} vì dữ liệu đang được tham chiếu.";
        }

        if ($driverCode === 1452) {
            return "Dữ liệu liên kết không hợp lệ khi {$action}.";
        }

        return "Lỗi cơ sở dữ liệu khi {$action}. Vui lòng thử lại sau.";
    }
}
