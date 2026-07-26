<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Chặn user đã xác thực nhưng tài khoản không còn active.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($user->status ?? null) !== 'active') {
            // Thu hồi token hiện tại nếu đang dùng Sanctum token
            $token = $user->currentAccessToken();
            if ($token) {
                $token->delete();
            }

            return response()->json([
                'message' => 'Tài khoản đã bị khóa hoặc không hoạt động.',
            ], 401);
        }

        return $next($request);
    }
}
