<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseApiController
{
    /**
     * Đăng ký tài khoản mới và trả về token Sanctum.
     */
    public function register(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
                'phone' => ['required', 'string', 'max:20', 'unique:users,phone', 'regex:/^(0|\+84)(3|5|7|8|9)[0-9]{8}$/'],
                'password' => ['required', 'confirmed', Password::defaults()],
            ], [
                'phone.regex' => 'Số điện thoại không hợp lệ (VD: 0912345678).',
                'phone.unique' => 'Số điện thoại đã được sử dụng.',
                'email.unique' => 'Email đã được sử dụng.',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => $validated['password'],
            ]);

            $token = $user->createToken('spa-token')->plainTextToken;

            return response()->json([
                'message' => 'Đăng ký thành công.',
                'user' => $user,
                'token' => $token,
            ], 201);

        }, 'đăng ký tài khoản');
    }

    /**
     * Đăng nhập bằng email hoặc số điện thoại, trả về token Sanctum.
     */
    public function login(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $credentials = $request->validate([
                'login' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string'],
            ], [
                'login.required' => 'Vui lòng nhập email hoặc số điện thoại.',
            ]);

            $login = trim($credentials['login']);
            $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);

            $user = User::query()
                ->when(
                    $isEmail,
                    fn ($q) => $q->where('email', $login),
                    fn ($q) => $q->where('phone', $login),
                )
                ->first();

            if (! $user || ! Hash::check($credentials['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'login' => ['Email/số điện thoại hoặc mật khẩu không đúng.'],
                ]);
            }

            if ($user->status !== 'active') {
                throw ValidationException::withMessages([
                    'login' => ['Tài khoản đã bị khóa hoặc không hoạt động.'],
                ]);
            }

            $user->load(['nhanVien.vaiTro']);

            $token = $user->createToken('spa-token')->plainTextToken;

            return response()->json([
                'message' => 'Đăng nhập thành công.',
                'user' => $user,
                'token' => $token,
            ]);

        }, 'đăng nhập');
    }

    /**
     * Đăng xuất: thu hồi token hiện tại.
     */
    public function logout(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Đăng xuất thành công.',
            ]);

        }, 'đăng xuất');
    }

    /**
     * Lấy thông tin user đang đăng nhập.
     */
    public function me(Request $request): JsonResponse
    {
        return $this->handleApi(function () use ($request) {
            return response()->json([
                'user' => $request->user()->load(['nhanVien.vaiTro']),
            ]);

        }, 'lấy thông tin tài khoản');
    }
}
