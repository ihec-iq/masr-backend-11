<?php

use App\Http\Controllers\Api\v1\BackupAdminController;
use App\Http\Controllers\Api\v1\BackupController;
use App\Http\Controllers\Api\v1\BackupHealthController;
use App\Http\Controllers\Api\v1\BackupSettingsController;
use Illuminate\Support\Facades\Route;
// كل مسارات النسخ الاحتياطي محمية بـ auth:sanctum ومحدودة بـ throttle:backup
Route::middleware(['auth:sanctum', 'maintenance', 'locale', 'throttle:backup'])->prefix('backup')->group(function () {
    // الإعدادات
    Route::get('settings', [BackupSettingsController::class, 'show']);
    Route::post('settings', [BackupSettingsController::class, 'update']);

    // تشغيل يدوي + قائمة + حذف + رابط تحميل مؤقت + استعادة
    Route::post('run', [BackupController::class, 'runNow']);
    Route::get('list', [BackupController::class, 'list']);             // قائمة النسخ من القرص
    Route::get('logs', [BackupController::class, 'logs']);             // سجلات النسخ الاحتياطي
    Route::delete('delete', [BackupController::class, 'delete']);      // ?path=...
    Route::delete('delete_all', [BackupController::class, 'delete_all']);      // حذف جميع النسخ
    Route::delete('deleteAllByLogs', [BackupController::class, 'deleteAllByLogs']); // حذف حسب السجلات
    Route::post('temp-link', [BackupController::class, 'tempLink']);   // {path} → رابط مؤقت
    Route::post('restore', [BackupController::class, 'restore']);      // استعادة DB/Files

    // اختبار البريد الإلكتروني
    Route::post('test-email', [BackupController::class, 'testEmail']); // إرسال email تجريبي
    Route::get('preview-email', [BackupController::class, 'previewEmail']); // معاينة Email في المتصفح

    // اختبار Telegram و Webhook
    Route::post('test-telegram', [BackupController::class, 'testTelegram']); // اختبار إرسال Telegram
    Route::post('test-webhook', [BackupController::class, 'testWebhook']); // اختبار إرسال Webhook
});

Route::middleware(['auth:sanctum', 'maintenance', 'locale', 'throttle:backup'])->group(function () {
    // فحص الحالة
    Route::get('health/backup', [BackupHealthController::class, 'status']);
    Route::prefix('backup/admins')->group(function () {
        Route::get('/', [BackupAdminController::class, 'index']);
        Route::post('/', [BackupAdminController::class, 'store']);
        Route::post('/{backupAdmin}', [BackupAdminController::class, 'update']);
        Route::delete('/{backupAdmin}', [BackupAdminController::class, 'destroy']);
    });
});
