<?php

use App\Http\Controllers\Api\AuthController;
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

    // Nhân sự (users)
    Route::get('/users', [UserController::class, 'index']);

    // Phòng ban
    Route::apiResource('phong-ban', PhongBanController::class);
});
