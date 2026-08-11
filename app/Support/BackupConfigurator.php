<?php

namespace App\Support;

use App\Models\BackupSetting;
use Illuminate\Support\Facades\Config;

class BackupConfigurator
{
    public static function apply(?BackupSetting $settings = null): void
    {
        $settings ??= BackupSetting::first();
        if (!$settings || !$settings->enabled) return;

        // اسم النسخ الاحتياطي
        Config::set('backup.backup.name', config('app.name', 'laravel'));

        // وجهة التخزين
        Config::set('backup.destination.disks', [$settings->disk]);

        // قواعد البيانات (إن وُجد تعدد قواعد نأخذ المحددة، وإلا mysql)
        $dbs = ($settings->multi_db && $settings->selected_databases)
            ? $settings->selected_databases
            : ['mysql'];
        Config::set('backup.backup.source.databases', $dbs);

        // 🔒 الملفات: فقط storage/app/public (حسب طلبك)
        if ($settings->include_files) {
            Config::set('backup.backup.source.files', [
                'include' => [ storage_path('app/public') ],
                'exclude' => [],
                'follow_links' => false,
                'ignore_unreadable_directories' => false,
                'relative_path' => base_path(),
            ]);
        } else {
            Config::set('backup.backup.source.files', [
                'include' => [],
                'exclude' => [],
                'follow_links' => false,
                'ignore_unreadable_directories' => false,
                'relative_path' => base_path(),
            ]);
        }

        // سياسات الاحتفاظ
        Config::set('backup.cleanup.default_strategy.keep_all_backups_for_days', $settings->keep_daily_days);
        Config::set('backup.cleanup.default_strategy.keep_daily_backups_for_days', $settings->keep_daily_days);
        Config::set('backup.cleanup.default_strategy.keep_weekly_backups_for_weeks', $settings->keep_weekly_weeks);
        Config::set('backup.cleanup.default_strategy.keep_monthly_backups_for_months', $settings->keep_monthly_months);
        Config::set('backup.cleanup.default_strategy.keep_yearly_backups_for_years', $settings->keep_yearly_years);
        Config::set('backup.cleanup.default_strategy.delete_oldest_backups_when_using_more_megabytes_than', $settings->max_storage_mb);

        // إشعارات البريد الافتراضية (لن نستخدمها كثيرًا لأننا نرسل عبر NotificationManager)
        $emails = collect(explode(',', (string) $settings->emails))->map('trim')->filter()->values()->all();
        Config::set('backup.notifications.mail.to', $emails);

        // المنطقة الزمنية
        Config::set('app.timezone', $settings->timezone ?? 'Asia/Baghdad');
    }
}
