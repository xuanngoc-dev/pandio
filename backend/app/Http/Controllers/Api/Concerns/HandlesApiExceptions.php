<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
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
            return response()->json([
                'message' => "Không tìm thấy dữ liệu khi {$action}.",
            ], 404);
        } catch (QueryException $e) {
            report($e);

            return response()->json([
                'message' => $this->mapQueryExceptionMessage($e, $action),
            ], $this->mapQueryExceptionStatus($e));
        } catch (Throwable $e) {
            report($e);

            $payload = [
                'message' => config('app.debug')
                    ? ($e->getMessage() !== '' ? $e->getMessage() : "Đã xảy ra lỗi khi {$action}.")
                    : "Đã xảy ra lỗi khi {$action}. Vui lòng thử lại sau.",
            ];

            if (config('app.debug')) {
                $payload['exception'] = class_basename($e);
                $payload['file'] = $e->getFile();
                $payload['line'] = $e->getLine();
            }

            return response()->json($payload, 500);
        }
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
