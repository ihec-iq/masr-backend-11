<?php

use App\Http\Controllers\Api\v1\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard')->middleware(['auth:sanctum', 'throttle:api'])->group(function () {
    // Overview
    Route::get('/overview', [DashboardController::class, 'overview']);
    
    // Employee Analytics
    Route::get('/employees/stats', [DashboardController::class, 'employeeStats']);
    Route::get('/employees/by-type', [DashboardController::class, 'employeesByType']);
    Route::get('/employees/by-section', [DashboardController::class, 'employeesBySection']);
    
    // Vacation Analytics
    Route::get('/vacations/stats', [DashboardController::class, 'vacationStats']);
    Route::get('/vacations/trends', [DashboardController::class, 'vacationTrends']);
    
    // Stock Analytics
    Route::get('/stock/stats', [DashboardController::class, 'stockStats']);
    Route::get('/stock/low-stock', [DashboardController::class, 'lowStockItems']);
    
    // System Analytics
    Route::get('/system/stats', [DashboardController::class, 'systemStats']);
    Route::get('/activity', [DashboardController::class, 'activityStats']);
    
    // Cache Management
    Route::post('/cache/invalidate', [DashboardController::class, 'invalidateCache']);
});
