<?php

use App\Http\Controllers\Api\v1\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware(['locale'])->group(function () {
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:auth'); // 5 requests per minute
    // /register محذوف: AuthController لا يملك register، فكان المسار يرمي 500.
    // إنشاء المستخدمين يتم عبر POST /api/user (UserController@store).
});
Route::get('/me', [AuthController::class, 'me'])->middleware(['auth:sanctum', 'locale', 'maintenance']);
Route::get('/profile', [AuthController::class, 'profile'])->middleware(['auth:sanctum', 'locale', 'maintenance']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware(['auth:sanctum', 'locale', 'maintenance']);
