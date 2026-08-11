<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BackupSetting extends Model
{
    protected $fillable = [
        'cron', 'timezone', 'max_storage_mb',
        'include_files', 'include_paths', 'exclude_paths', 'multi_db', 'selected_databases',
        'keep_daily_days', 'keep_weekly_weeks', 'keep_monthly_months', 'keep_yearly_years',
        'disk', 'drive_folder', 'temp_link_expiry', 'checksum_enabled',
        'notify_enabled', 'notify_on',
        'telegram_enabled', 'email_enabled', 'webhook_enabled',
        'emails', 'telegram_bot_token',
        'telegram_chat_ids', 'webhook_urls', 'webhook_secret', 'stale_hours',
        'last_run_at',
        'auto_backup_enabled', 'auto_backup_interval', 'auto_backup_type', 'last_auto_backup_at',
    ];

    protected $casts = [
        'include_files' => 'boolean',
        'multi_db' => 'boolean',
        'checksum_enabled' => 'boolean',
        'notify_enabled' => 'boolean',
        'telegram_enabled' => 'boolean',
        'email_enabled' => 'boolean',
        'webhook_enabled' => 'boolean',
        'auto_backup_enabled' => 'boolean',
        'include_paths' => 'array',
        'exclude_paths' => 'array',
        'selected_databases' => 'array',
        'last_run_at' => 'datetime',
        'last_auto_backup_at' => 'datetime',
    ];
}
