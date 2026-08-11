<?php

namespace App\Console;

use App\Jobs\RunBackupJob;
use App\Models\BackupSetting;
use Cron\CronExpression;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    
    protected function schedule(\Illuminate\Console\Scheduling\Schedule $schedule): void
    {
        // موجود سابقاً: dynamic-backup-runner ...

        // النسخ الاحتياطي التلقائي - يتم فحصه كل دقيقة
        // الـ Command نفسه يتحقق من الفترة الزمنية المحددة في الإعدادات
        $schedule->command('backup:auto')
            ->everyMinute()
            ->name('auto-backup-runner')
            ->withoutOverlapping();

        // مراقبة Stale
        $schedule->call(function () {
            $s = \App\Models\BackupSetting::first();
            if (!$s || !$s->notify_enabled) return;

            $staleHours = (int) ($s->stale_hours ?? 48);
            $last = \App\Models\BackupLog::where('status', 'success')->latest('finished_at')->first();
            if (!$last) return;

            $age = now()->diffInHours($last->finished_at);
            if ($age <= $staleHours) return;

            // أرسل تنبيه أول ثم كل 24 ساعة
            $key = 'backup_stale_last_notice_at';
            $lastNotice = cache()->get($key);
            if (!$lastNotice || now()->diffInHours($lastNotice) >= 24) {
                // اصنع Log افتراضي لتمريره للـ NotificationManager
                $log = new \App\Models\BackupLog([
                    'status' => 'failed', // نعامله كتحذير
                    'message' => "No fresh backups for {$age} hours",
                    'backup_paths' => [],
                    'total_size' => 0,
                ]);
                \App\Support\NotificationManager::notify($log);
                cache()->forever($key, now());
            }
        })->everyMinute()->name('backup-stale-monitor');
    }
}
