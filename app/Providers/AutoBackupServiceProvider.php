<?php

namespace App\Providers;

use App\Jobs\RunBackupJob;
use App\Models\BackupSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AutoBackupServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // يشتغل فقط بعد اكتمال boot التطبيق
        // ويشتغل فقط في web و api contexts (مو في console)
        if ($this->app->runningInConsole()) {
            return;
        }

        // استخدام defer للتنفيذ بعد انتهاء الـ request
        // هذا يضمن عدم تأخير الـ Response للمستخدم
        $this->app->terminating(function () {
            $this->checkAndRunAutoBackup();
        });
    }

    /**
     * فحص وتشغيل النسخ الاحتياطي التلقائي إذا حان وقته
     */
    protected function checkAndRunAutoBackup(): void
    {
        try {
            // استخدام Cache Lock لمنع التنفيذ المتعدد في نفس الوقت
            $lock = Cache::lock('auto_backup_check', 10);

            if (!$lock->get()) {
                // عملية أخرى تفحص الآن، نتجاوز
                return;
            }

            // جلب الإعدادات
            $settings = BackupSetting::first();

            if (!$settings) {
                $lock->release();
                return;
            }

            // التحقق من تفعيل النسخ التلقائي
            if (!$settings->auto_backup_enabled) {
                $lock->release();
                return;
            }

            // حساب الفترة الزمنية
            $interval = $settings->auto_backup_interval ?? 1440;
            $lastAutoBackup = $settings->last_auto_backup_at;

            $shouldRun = false;

            if ($lastAutoBackup === null) {
                // أول مرة - شغّل فوراً
                $shouldRun = true;
            } else {
                $minutesSinceLastBackup = now()->diffInMinutes($lastAutoBackup);

                if ($minutesSinceLastBackup >= $interval) {
                    $shouldRun = true;
                }
            }

            if ($shouldRun) {
                $backupType = $settings->auto_backup_type ?? 'both';

                Log::info('AutoBackupServiceProvider: Triggering auto backup', [
                    'backup_type' => $backupType,
                    'last_backup' => $lastAutoBackup?->toDateTimeString(),
                    'interval' => $interval,
                ]);

                // تحديث آخر وقت تشغيل
                $settings->forceFill(['last_auto_backup_at' => now()])->save();

                // dispatch الـ Job
                RunBackupJob::dispatch('auto', $backupType);
            }

            $lock->release();
        } catch (\Throwable $e) {
            Log::error('AutoBackupServiceProvider: Error checking auto backup', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
