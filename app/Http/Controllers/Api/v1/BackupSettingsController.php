<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackupSettingsRequest;
use App\Jobs\RunBackupJob;
use App\Models\BackupSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BackupSettingsController extends Controller
{
    public function show()
    {
        return BackupSetting::firstOrFail();
    }

    public function update(BackupSettingsRequest $request)
    {
        Log::info('BackupSettingsController@update called', ['validated_data' => $request->validated()]);

        $settings = BackupSetting::firstOrFail();

        // التحقق من تفعيل النسخ التلقائي
        $wasAutoBackupEnabled = $settings->auto_backup_enabled;
        $newAutoBackupEnabled = $request->input('auto_backup_enabled', $wasAutoBackupEnabled);

        // تحديث الإعدادات
        $settings->fill($request->validated())->save();

        // إذا تم تفعيل النسخ التلقائي للتو، أو كان مفعّل بدون نسخة سابقة
        $shouldTriggerBackup = false;

        // حالة 1: تم التفعيل للتو (من false إلى true)
        if (!$wasAutoBackupEnabled && $newAutoBackupEnabled) {
            $shouldTriggerBackup = true;
            Log::info('Auto backup just enabled');
        }
        // حالة 2: مفعّل لكن ما في نسخة تلقائية سابقة
        elseif ($newAutoBackupEnabled && $settings->last_auto_backup_at === null) {
            $shouldTriggerBackup = true;
            Log::info('Auto backup enabled but no previous backup found');
        }

        if ($shouldTriggerBackup) {
            // تحديد نوع النسخة
            $backupType = $settings->auto_backup_type ?? 'both';

            // تحديث وقت آخر نسخة تلقائية
            $settings->forceFill(['last_auto_backup_at' => now()])->save();

            // تشغيل نسخة فورية
            RunBackupJob::dispatch('auto', $backupType);

            Log::info('Immediate backup dispatched', [
                'backup_type' => $backupType,
                'interval' => $settings->auto_backup_interval,
            ]);
        }

        return $settings->refresh();
    }
}
