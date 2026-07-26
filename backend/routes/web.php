<?php

use App\Http\Controllers\ApiDocsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/api-docs/login', [ApiDocsController::class, 'showLogin'])->name('api-docs.login');
    Route::post('/api-docs/login', [ApiDocsController::class, 'login'])->name('api-docs.login.submit');
});

Route::middleware('auth')->group(function () {
    Route::get('/api-docs', [ApiDocsController::class, 'index'])->name('api-docs');
    Route::post('/api-docs/logout', [ApiDocsController::class, 'logout'])->name('api-docs.logout');
});
