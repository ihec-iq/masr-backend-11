<?php

namespace App\Console\Commands;

use App\Jobs\RunBackupJob;
use App\Models\BackupSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:auto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run automatic backup if enabled and interval has passed';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $settings = BackupSetting::first();

        // التحقق من وجود الإعدادات
        if (!$settings) {
            $this->error('No backup settings found.');
            Log::warning('AutoBackupCommand: No backup settings found.');
            return self::FAILURE;
        }

        // التحقق من تفعيل النسخ التلقائي
        if (!$settings->auto_backup_enabled) {
            $this->info('Auto backup is disabled.');
            return self::SUCCESS;
        }

        // التحقق من الفترة الزمنية
        $interval = $settings->auto_backup_interval ?? 1440; // القيمة الافتراضية: يوم واحد
        $lastAutoBackup = $settings->last_auto_backup_at;

        $shouldRun = false;

        if ($lastAutoBackup === null) {
            // لم يتم تشغيل نسخة تلقائية من قبل
            $shouldRun = true;
            $this->info('No previous auto backup found. Running first auto backup.');
        } else {
            // حساب الفرق بالدقائق
            $minutesSinceLastBackup = now()->diffInMinutes($lastAutoBackup);

            if ($minutesSinceLastBackup >= $interval) {
                $shouldRun = true;
                $this->info("Last auto backup was {$minutesSinceLastBackup} minutes ago. Interval is {$interval} minutes. Running backup.");
            } else {
                $remainingMinutes = $interval - $minutesSinceLastBackup;
                $this->info("Too soon for auto backup. {$remainingMinutes} minutes remaining.");
            }
        }

        if ($shouldRun) {
            // تشغيل النسخ الاحتياطي
            $backupType = $settings->auto_backup_type ?? 'both';

            $this->info("Starting auto backup (type: {$backupType})...");

            Log::info('AutoBackupCommand: Dispatching RunBackupJob', [
                'backup_type' => $backupType,
                'trigger_type' => 'auto'
            ]);

            // تحديث آخر وقت تشغيل تلقائي
            $settings->forceFill(['last_auto_backup_at' => now()])->save();

            // إرسال الـ Job
            RunBackupJob::dispatch('auto', $backupType);

            $this->info('Auto backup job dispatched successfully.');

            return self::SUCCESS;
        }

        return self::SUCCESS;
    }
}
