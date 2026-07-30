<?php

use App\Http\Controllers\ApiDocsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [ApiDocsController::class, 'showLogin'])->name('api-docs.login');
    Route::post('/login', [ApiDocsController::class, 'login'])->name('api-docs.login.submit');
});

Route::middleware('auth')->group(function () {
Route::get('/', [ApiDocsController::class, 'index'])->name('api-docs');
    Route::post('/logout', [ApiDocsController::class, 'logout'])->name('api-docs.logout');
});
