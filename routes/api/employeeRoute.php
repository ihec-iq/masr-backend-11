<?php

use App\Http\Controllers\Api\v1\BonusController; 
use App\Http\Controllers\Api\v1\BonusJobTitleController;
use App\Http\Controllers\Api\v1\EmployeeCenterController;
use App\Http\Controllers\Api\v1\EmployeeController;
use App\Http\Controllers\Api\v1\EmployeePositionController;
use App\Http\Controllers\Api\v1\EmployeeTypeController;
use App\Http\Controllers\Api\v1\HrDocumentController;
use App\Http\Controllers\Api\v1\HrDocumentTypeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'maintenance', 'locale'])->group(function () {
    
    Route::prefix('/employee')->group(function () {
        Route::get('/', [EmployeeController::class, 'index']);
        Route::get('/lite', [EmployeeController::class, 'getLite']);
        Route::get('/filter', [EmployeeController::class, 'filter']);
        Route::get('/filter/with/bonus', [EmployeeController::class, 'filterWithBonus']);
        Route::get('/filter/lite', [EmployeeController::class, 'filterLite']);
        Route::get('/{employee}', [EmployeeController::class, 'show']);
        Route::get('/show/lite/{employee}', [EmployeeController::class, 'showLite']);
        Route::get('/show/bonus/lite/{employee}', [EmployeeController::class, 'showLiteBonus']);
        Route::post('/store', [EmployeeController::class, 'store']);
        Route::post('/update/{employee}', [EmployeeController::class, 'update']);
        Route::post('/update/bonus/{employee}', [EmployeeController::class, 'updateBonusInfo']);
        Route::delete('/delete/{employee}', [EmployeeController::class, 'destroy']);
        Route::get('/bonus/check', [EmployeeController::class, 'bonusCheck']);
        Route::get('/bonus/calculate', [EmployeeController::class, 'bonusCalculate']);
    });

    Route::prefix('/employee_type')->group(function () {
        Route::get('/', [EmployeeTypeController::class, 'index']);
        Route::get('/{id}', [EmployeeTypeController::class, 'show']);
        Route::post('/store', [EmployeeTypeController::class, 'store']);
        Route::post('/update/{id}', [EmployeeTypeController::class, 'update']);
        Route::delete('/delete/{id}', [EmployeeTypeController::class, 'destroy']);
    });

    Route::prefix('/employee_position')->group(function () {
        Route::get('/', [EmployeePositionController::class, 'index']);
        Route::get('/{id}', [EmployeePositionController::class, 'show']);
        Route::post('/store', [EmployeePositionController::class, 'store']);
        Route::post('/update/{id}', [EmployeePositionController::class, 'update']);
        Route::delete('/delete/{id}', [EmployeePositionController::class, 'destroy']);
    });

    Route::prefix('/employee_center')->group(function () {
        Route::get('/', [EmployeeCenterController::class, 'index']);
        Route::get('/{id}', [EmployeeCenterController::class, 'show']);
        Route::post('/store', [EmployeeCenterController::class, 'store']);
        Route::post('/update/{id}', [EmployeeCenterController::class, 'update']);
        Route::delete('/delete/{id}', [EmployeeCenterController::class, 'destroy']);
    });

    Route::prefix('/hr_document')->group(function () {
        Route::get('/', [HrDocumentController::class, 'index']);
        Route::get('/filter', [HrDocumentController::class, 'filter']);
        Route::get('/{id}', [HrDocumentController::class, 'show']);
        Route::get('/getHrByEmployee/{id}', [HrDocumentController::class, 'check_bonus_employee']);
        Route::get('/getHrByEmployeeTotal/{id}', [HrDocumentController::class, 'check_bonus_employee_total']);
        Route::get('/updateEmployeeDateBonusByEmployee/{id}', [HrDocumentController::class, 'update_employee_date_bonus']);
        Route::post('/store', [HrDocumentController::class, 'store']);
        Route::post('/update/{id}', [HrDocumentController::class, 'update']);
        Route::delete('/delete/{id}', [HrDocumentController::class, 'destroy']);
    });

    Route::prefix('/hr_document_type')->group(function () {
        Route::get('/', [HrDocumentTypeController::class, 'index']);
    });

    Route::prefix('/bonus')->group(function () {
        Route::get('/', [BonusController::class, 'index']);
        Route::get('/filter', [BonusController::class, 'filter']);
        Route::get('/{id}', [BonusController::class, 'show']);
        Route::post('/store', [BonusController::class, 'store']);
        Route::post('/update/{id}', [BonusController::class, 'update']);
        Route::delete('/delete/{id}', [BonusController::class, 'destroy']);
    });

    Route::prefix('/bonus_job_title')->group(function () {
        Route::get('', [BonusJobTitleController::class, 'index']);
        Route::get('/filter', [BonusJobTitleController::class, 'filter']);
        Route::post('/store', [BonusJobTitleController::class, 'store']);
        Route::post('/update/{id}', [BonusJobTitleController::class, 'update']);
        Route::delete('/delete/{id}', [BonusJobTitleController::class, 'destroy']);
        Route::get('/{id}', [BonusJobTitleController::class, 'show']);
    });

    Route::prefix('/study')->group(function () {
        Route::get('', [BonusController::class, 'Study']);
    });

    Route::prefix('/certificate')->group(function () {
        Route::get('', [BonusController::class, 'Certificate']);
    });

    Route::prefix('/bonus_degree_stage')->group(function () {
        Route::get('', [BonusController::class, 'Bonus_degree_stage']);
    });
});
