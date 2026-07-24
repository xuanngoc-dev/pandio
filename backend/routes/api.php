<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CauHinhTaiKhoanThanhToanController;
use App\Http\Controllers\Api\CauHinhThongTinStudioController;
use App\Http\Controllers\Api\IpDiemDanhController;
use App\Http\Controllers\Api\PhongBanController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Prefix mặc định: /api
*/

// Auth công khai (không cần token)
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Auth bảo vệ bởi Sanctum
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'me']);

    // Nhân sự (users + nhan_vien)
    Route::post('/users/upload-hinh-anh', [UserController::class, 'uploadHinhAnh']);
    Route::apiResource('users', UserController::class);

    // Phòng ban
    Route::apiResource('phong-ban', PhongBanController::class);

    // IP điểm danh
    Route::apiResource('ip-diem-danh', IpDiemDanhController::class);

    // Cấu hình thông tin studio
    Route::post('/cau-hinh-thong-tin-studio/upload-logo', [CauHinhThongTinStudioController::class, 'uploadLogo']);
    Route::apiResource('cau-hinh-thong-tin-studio', CauHinhThongTinStudioController::class);

    // Cấu hình tài khoản thanh toán
    Route::apiResource('cau-hinh-tai-khoan-thanh-toan', CauHinhTaiKhoanThanhToanController::class);
});
