<?php

use App\Http\Controllers\Api\v1\LogFileController;
use App\Http\Controllers\Api\v1\SettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'maintenance', 'locale'])
    ->prefix('setting')
    ->group(function () {
        // Collection
        Route::get('/', [SettingController::class, 'index']);
        Route::post('/store', [SettingController::class, 'store']);

        // Key-based endpoints (must come before parameter routes)
        Route::get('/show/key', [SettingController::class, 'showByKey']);
        Route::post('/updatebykey', [SettingController::class, 'updateByKey']);

        // Item-based endpoints (parameterized routes last to avoid conflicts)
        Route::post('/update/{setting}', [SettingController::class, 'update']);
        Route::delete('/delete/{setting}', [SettingController::class, 'destroy']);
        Route::get('/{setting}', [SettingController::class, 'show']);
    });

Route::middleware(['auth:sanctum', 'maintenance', 'locale'])
    ->prefix('logs')
    ->group(function () {
        Route::get('/',         [LogFileController::class, 'meta']);     // basic info & file size
        Route::get('/tail',     [LogFileController::class, 'tail']);     // last N lines
        Route::get('/download', [LogFileController::class, 'download']); // download/open
        Route::post('/upload',  [LogFileController::class, 'upload']);   // upload/replace log file
        Route::delete('/',      [LogFileController::class, 'destroy']);  // delete/clear
    });
