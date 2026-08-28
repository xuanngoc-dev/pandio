<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Exceptions\ApiExceptionHandler;
use App\Support\QueryExceptionMapper;
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
            $status = QueryExceptionMapper::status($e);
            $this->logHandledException($e, $action, $status);

            return $this->errorJson(
                QueryExceptionMapper::message($e, $action),
                $status,
                $e
            );
        } catch (Throwable $e) {
            $this->logHandledException($e, $action, 500);

            return $this->errorJson(
                "Đã xảy ra lỗi khi {$action}. Vui lòng thử lại sau.",
                500,
                $e
            );
        }
    }

    protected function logHandledException(Throwable $e, string $action, int $status): void
    {
        // report() → laravel.log; channel api → api-YYYY-MM-DD.log
        report($e);

        try {
            $context = ApiExceptionHandler::exceptionContext($e, request(), $status);
            $context['action'] = $action;
            $context['controller'] = static::class;

            $level = $status >= 500 ? 'error' : 'warning';

            if (array_key_exists('api', config('logging.channels', []))) {
                Log::channel('api')->{$level}('API handleApi Exception', $context);
            } else {
                Log::{$level}('[api-fallback] API handleApi Exception', $context);
            }
        } catch (Throwable) {
            // Không để logging làm hỏng response API
        }
    }

    protected function errorJson(string $message, int $status, ?Throwable $e = null): JsonResponse
    {
        $payload = ['message' => $message];

        if ($requestId = request()->attributes->get('api_log_request_id')) {
            $payload['request_id'] = $requestId;
        }

        // Debug details only in meta fields — never put raw SQL into `message`
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
}
