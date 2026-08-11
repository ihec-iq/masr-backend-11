<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\v1\PromotionController;

// Define routes for promotions
Route::middleware(['auth:sanctum', 'maintenance', 'locale'])->prefix('promotions')->group(function () {
    Route::get('/', [PromotionController::class, 'index'])->name('promotions.index');
    Route::post('/', [PromotionController::class, 'store'])->name('promotions.store');
    Route::get('/{promotion}', [PromotionController::class, 'show'])->name('promotions.show');
    Route::post('/{promotion}', [PromotionController::class, 'update'])->name('promotions.update');
    Route::delete('/{promotion}', [PromotionController::class, 'destroy'])->name('promotions.destroy');
});     