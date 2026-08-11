<?php

use App\Http\Controllers\Api\v1\RetrievalVoucherController;
use App\Http\Controllers\Api\v1\RetrievalVoucherItemController;
use App\Http\Controllers\Api\v1\DirectVoucherController;
use App\Http\Controllers\Api\v1\DirectVoucherItemController;
use App\Http\Controllers\Api\v1\InputVoucherController;
use App\Http\Controllers\Api\v1\InputVoucherItemController;
use App\Http\Controllers\Api\v1\InputVoucherStateController;
use App\Http\Controllers\Api\v1\InventoryController;
use App\Http\Controllers\Api\v1\ItemCategoryController;
use App\Http\Controllers\Api\v1\ItemController;
use App\Http\Controllers\Api\v1\OutputVoucherController;
use App\Http\Controllers\Api\v1\OutputVoucherItemController;
use App\Http\Controllers\Api\v1\RetrievalVoucherItemTypeController;
use App\Http\Controllers\Api\v1\StockController;
use App\Http\Controllers\Api\v1\StoreController;
use App\Http\Controllers\Api\v1\VoucherItemHistoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'maintenance', 'locale'])->prefix('/stockSys')->group(function () {
    Route::prefix('/store')->group(function () {
        Route::get('/', [StoreController::class, 'index']);
        Route::get('/filter', [StoreController::class, 'filter']);
        Route::get('/total', [StoreController::class, 'total']);
        Route::get('/summation', [StoreController::class, 'summation']);
        Route::get('/barrenSection', action: [StoreController::class, 'barrenSection']);
        Route::get('/barrenSection/{id}', action: [StoreController::class, 'barrenSectionId']);
        Route::get('/item/history/{id}', [StoreController::class, 'showItemHistory']);
    });
    Route::prefix('/inventory')->group(function () {
        Route::get('/balances', [InventoryController::class, 'balances']);
        Route::get('/balance',  [InventoryController::class, 'balance']);
        Route::get('/movements', [InventoryController::class, 'movements']);
    });

    Route::prefix('/stock')->group(function () {
        Route::get('/', [StockController::class, 'index']);
        Route::get('/{id}', [StockController::class, 'show']);
        Route::post('/store', [StockController::class, 'store']);
        Route::post('/update/{id}', [StockController::class, 'update']);
        Route::delete('/delete/{id}', [StockController::class, 'destroy']);
    });

    Route::prefix('/itemCategory')->group(function () {
        Route::get('/', [ItemCategoryController::class, 'index']);
        Route::get('/filter', [ItemCategoryController::class, 'filter']);
        Route::get('/{id}', [ItemCategoryController::class, 'show']);
        Route::post('/store', [ItemCategoryController::class, 'store']);
        Route::post('/update/{itemCategory}', [ItemCategoryController::class, 'update']);
        Route::delete('/delete/{id}', [ItemCategoryController::class, 'destroy']);
    });
    Route::prefix('/item')->group(function () {
        Route::get('/', [ItemController::class, 'index']);
        Route::get('/filter', [ItemController::class, 'filter']);
        Route::get('/{id}', [ItemController::class, 'show']);
        Route::get('/history/{id}', [ItemController::class, 'showHistory']);
        Route::post('/store', [ItemController::class, 'store']);
        Route::post('/update/{id}', [ItemController::class, 'update']);
        Route::delete('/delete/{id}', [ItemController::class, 'destroy']);
    });
    Route::prefix('/inputVoucherState')->group(function () {
        Route::get('/', [InputVoucherStateController::class, 'index']);
        Route::get('/{id}', [InputVoucherStateController::class, 'show']);
        Route::post('/store', [InputVoucherStateController::class, 'store']);
        Route::post('/update/{id}', [InputVoucherStateController::class, 'update']);
        Route::delete('/delete/{id}', [InputVoucherStateController::class, 'destroy']);
    });
    Route::prefix('/inputVoucher')->group(function () {
        Route::get('/', [InputVoucherController::class, 'index']);
        Route::get('/filter', [InputVoucherController::class, 'filter']);
        Route::get('/{inputVoucher}', [InputVoucherController::class, 'show']);
        Route::post('/store', [InputVoucherController::class, 'store']);
        Route::post('/update/{inputVoucher}', [InputVoucherController::class, 'update']);
        Route::delete('/delete/{id}', [InputVoucherController::class, 'destroy']);
    });
    Route::prefix('/inputVoucherItem')->group(function () {
        Route::get('/', [InputVoucherItemController::class, 'index']);
        Route::get('/getAvailableItemsVSelect', [InputVoucherItemController::class, 'getAvailableItemsVSelect']);
        Route::get('/getAvailableItemsVSelectByEmployeeId/{employeeId}', [InputVoucherItemController::class, 'getAvailableItemsVSelectByEmployeeId']);
        Route::get('/getAllItemsVSelect/{storeId}', [InputVoucherItemController::class, 'getAllItemsVSelect']);
        Route::get('/filter', [InputVoucherItemController::class, 'filter']);
        Route::get('/{inputVoucherItem}', [InputVoucherItemController::class, 'show']);
        Route::post('/store', [InputVoucherItemController::class, 'store']);
        Route::post('/update/{inputVoucherItem}', [InputVoucherItemController::class, 'update']);
        Route::delete('/delete/{id}', [InputVoucherItemController::class, 'destroy']);
    });
    Route::prefix('/outputVoucher')->group(function () {
        Route::get('/', [OutputVoucherController::class, 'index']);
        Route::get('/filter', [OutputVoucherController::class, 'filter']);
        Route::get('/checkBillExists', [OutputVoucherController::class, 'checkBillExists']);
        Route::get('/{outputVoucher}', [OutputVoucherController::class, 'show']);
        Route::post('/store', [OutputVoucherController::class, 'store']);
        Route::post('/update/{outputVoucher}', [OutputVoucherController::class, 'update']);
        Route::delete('/delete/{id}', [OutputVoucherController::class, 'destroy']);
    });
    Route::prefix('/outputVoucherItem')->group(function () {
        Route::get('/', [OutputVoucherItemController::class, 'index']);
        Route::get('/filter', [OutputVoucherItemController::class, 'filter']);
        Route::get('/getItems', [OutputVoucherItemController::class, 'getItems']);
        Route::get('/{outputVoucherItem}', [OutputVoucherItemController::class, 'show']);
        Route::post('/store', [OutputVoucherItemController::class, 'store']);
        Route::post('/update/{outputVoucherItem}', [OutputVoucherItemController::class, 'update']);
        Route::delete('/delete/{id}', [OutputVoucherItemController::class, 'destroy']);
    });
    Route::prefix('/retrievalVoucher')->group(function () {
        Route::get('', [RetrievalVoucherController::class, 'index']);
        Route::get('/filter', [RetrievalVoucherController::class, 'filter']);
        Route::get('/{retrievalVoucher}', [RetrievalVoucherController::class, 'show']);
        Route::post('/store', [RetrievalVoucherController::class, 'store']);
        Route::post('/update/{retrievalVoucher}', [RetrievalVoucherController::class, 'update']);
        Route::delete('/delete/{id}', [RetrievalVoucherController::class, 'destroy']);
    });
    Route::prefix('/retrievalVoucherItem')->group(function () {
        Route::get('', [RetrievalVoucherItemController::class, 'index']);
        Route::get('/filter', [RetrievalVoucherItemController::class, 'filter']);
        Route::get('/{retrievalVoucherItem}', [RetrievalVoucherItemController::class, 'show']);
        Route::post('/store', [RetrievalVoucherItemController::class, 'store']);
        Route::post('/update/{retrievalVoucherItem}', [RetrievalVoucherItemController::class, 'update']);
        Route::delete('/delete/{id}', [RetrievalVoucherItemController::class, 'destroy']);
    });
    Route::prefix('/retrievalVoucherItemType')->group(function () {
        Route::get('', [RetrievalVoucherItemTypeController::class, 'index']);
    });

    Route::prefix('/directVoucher')->group(function () {
        Route::get('', [DirectVoucherController::class, 'index']);
        Route::get('/filter', [DirectVoucherController::class, 'filter']);
        Route::get('/{directVoucher}', [DirectVoucherController::class, 'show']);
        Route::post('/store', [DirectVoucherController::class, 'store']);
        Route::post('/update/{directVoucher}', [DirectVoucherController::class, 'update']);
        Route::delete('/delete/{id}', [DirectVoucherController::class, 'destroy']);
    });
    Route::prefix('/directVoucherItem')->group(function () {
        Route::get('', [DirectVoucherItemController::class, 'index']);
        Route::get('/getItemsForVSelect', [DirectVoucherItemController::class, 'getItemsForVSelect']);
        Route::get('/filter', [DirectVoucherItemController::class, 'filter']);
        Route::get('/{directVoucherItem}', [DirectVoucherItemController::class, 'show']);
        Route::post('/store', [DirectVoucherItemController::class, 'store']);
        Route::post('/update/{directVoucherItem}', [DirectVoucherItemController::class, 'update']);
        Route::delete('/delete/{id}', [DirectVoucherItemController::class, 'destroy']);
    });

    Route::prefix('/voucherItemHistory')->group(function () {
        Route::get('', [VoucherItemHistoryController::class, 'index']);
        Route::get('/filter', [VoucherItemHistoryController::class, 'filter']);
        Route::get('/reportStorage', [VoucherItemHistoryController::class, 'reportStorage']);
        Route::get('/{outputVoucherItem}', [VoucherItemHistoryController::class, 'show']);
    });
});
