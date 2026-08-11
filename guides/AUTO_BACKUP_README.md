# Auto Backup Feature - Implementation Summary

## Overview
تم تطوير نظام النسخ الاحتياطي التلقائي الذي يسمح بجدولة النسخ الاحتياطية بشكل تلقائي حسب فترة زمنية محددة.

---

## Files Modified/Created

### 1. Database Migration
**File:** `database/migrations/2025_11_06_054332_add_auto_backup_fields_to_backup_settings_table.php`

Added 4 new columns to `backup_settings` table:
- `auto_backup_enabled` (boolean) - تفعيل/إيقاف النسخ التلقائي
- `auto_backup_interval` (integer) - الفترة الزمنية بالدقائق
- `auto_backup_type` (enum: db/files/both) - نوع النسخة التلقائية
- `last_auto_backup_at` (timestamp) - آخر وقت تشغيل تلقائي

### 2. Model Updated
**File:** `app/Models/BackupSetting.php`

- Added new fields to `$fillable` array
- Added new fields to `$casts` array

### 3. Request Validation Updated
**File:** `app/Http/Requests/BackupSettingsRequest.php`

- Added `auto_backup_enabled` to `$booleanFields` in `prepareForValidation()`
- Added `auto_backup_interval` to `$integerFields`
- Added validation rules for the 3 new fields

### 4. Console Command Created
**File:** `app/Console/Commands/AutoBackupCommand.php`

**Command Signature:** `php artisan backup:auto`

**Logic:**
1. Checks if auto backup is enabled
2. Calculates time difference since last auto backup
3. If interval has passed, dispatches `RunBackupJob` with type 'auto'
4. Updates `last_auto_backup_at` timestamp

### 5. Kernel Scheduler Updated
**File:** `app/Console/Kernel.php`

**Added:**
```php
$schedule->command('backup:auto')
    ->everyMinute()
    ->name('auto-backup-runner')
    ->withoutOverlapping();
```

**How it works:**
- Runs every minute
- The command itself checks if interval has passed
- Uses `withoutOverlapping()` to prevent concurrent runs

### 6. Frontend Documentation
**File:** `AUTO_BACKUP_FRONTEND_INSTRUCTIONS.md`

Complete documentation for frontend developers in Arabic including:
- API endpoints
- Request/Response examples
- Validation rules
- UI/UX recommendations
- JavaScript helper functions
- Troubleshooting guide

---

## How It Works

### Flow Diagram

```
User enables auto backup in settings
         ↓
Laravel Scheduler runs every minute
         ↓
AutoBackupCommand checks:
- Is auto_backup_enabled = true?
- Is enabled = true?
- Has interval passed?
         ↓
    If YES → Dispatch RunBackupJob('auto', backup_type)
         ↓
    Update last_auto_backup_at = now()
         ↓
    RunBackupJob executes backup
         ↓
    Send notifications via enabled channels
```

### Key Features

1. **Interval-based scheduling**: User can set custom interval in minutes
2. **Flexible backup type**: Can backup DB only, files only, or both
3. **Independent from manual backups**: Auto backups are tracked separately
4. **Notification integration**: Uses existing notification channels (Telegram/Email/Webhook)
5. **No overlapping**: Uses `withoutOverlapping()` to prevent concurrent runs

---

## Configuration

### Database Fields

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| auto_backup_enabled | boolean | false | تفعيل/إيقاف النسخ التلقائي |
| auto_backup_interval | integer | 1440 | الفترة الزمنية بالدقائق |
| auto_backup_type | enum | both | نوع النسخة (db/files/both) |
| last_auto_backup_at | timestamp | null | آخر وقت تشغيل (read-only) |

### Common Intervals

- 60 = Every hour
- 360 = Every 6 hours
- 720 = Every 12 hours
- 1440 = Daily (24 hours)
- 10080 = Weekly
- 43200 = Monthly (30 days)

---

## API Usage

### Get Settings
```http
GET /api/backup/settings
```

**Response:**
```json
{
  "id": 1,
  
  "auto_backup_enabled": false,
  "auto_backup_interval": 1440,
  "auto_backup_type": "both",
  "last_auto_backup_at": null,
  ...
}
```

### Update Settings
```http
POST /api/backup/settings
```

**Request:**
```json
{
  "auto_backup_enabled": true,
  "auto_backup_interval": 1440,
  "auto_backup_type": "both"
}
```

**Note:** Do NOT send `last_auto_backup_at` - it's read-only and updated automatically.

---

## Testing

### Manual Test Commands

```bash
# Test the auto backup command
php artisan backup:auto

# Enable auto backup in database
php artisan tinker
>>> $s = App\Models\BackupSetting::first();
>>> $s->auto_backup_enabled = true;
>>> $s->auto_backup_interval = 1;  // 1 minute for testing
>>> $s->auto_backup_type = 'both';
>>> $s->save();

# Wait 1 minute, then check
php artisan backup:auto

# Check the logs
tail -f storage/logs/laravel.log

# Check backup logs
php artisan tinker
>>> App\Models\BackupLog::latest()->first();
```

### Verification Checklist

- [ ] Migration ran successfully
- [ ] New fields appear in `GET /api/backup/settings`
- [ ] Can update settings with new fields
- [ ] `php artisan backup:auto` executes without errors
- [ ] Scheduler is configured in `app/Console/Kernel.php`
- [ ] Cron job is set up on server (every minute)
- [ ] Backup logs show type = 'auto' when auto backup runs
- [ ] Notifications are sent after auto backup completes

---

## Server Setup

⚠️ **IMPORTANT:** النسخ الاحتياطي التلقائي **لن يعمل** بدون إعداد Laravel Scheduler على السيرفر.

---

### Production Server Setup

#### Step 1: Add Cron Job (Linux/Unix)

**1. Open crontab editor:**
```bash
crontab -e
```

**2. Add this line at the end:**
```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

**Replace `/path-to-your-project`** with your actual project path:
```bash
# Example:
* * * * * cd /var/www/msar-backend && php artisan schedule:run >> /dev/null 2>&1
```

**3. Save and exit** (in vim: press `ESC` then `:wq`)

**4. Verify cron job was added:**
```bash
crontab -l
```

---

#### Step 2: Verify Scheduler is Working

**Run this command to see scheduled tasks:**
```bash
php artisan schedule:list
```

**Expected output:**
```
┌─────────────────────────────────────────────────────────────────────────┐
│ backup:auto ............................ Next Due: 1 minute from now     │
│ backup-stale-monitor ................... Next Due: 1 minute from now     │
└─────────────────────────────────────────────────────────────────────────┘
```

**Test manually:**
```bash
# Run the scheduler once
php artisan schedule:run

# Check if backup:auto was triggered
tail -f storage/logs/laravel.log
```

---

### Windows Server Setup

**Option 1: Task Scheduler (Recommended)**

1. Open **Task Scheduler** (ابحث عن "Task Scheduler" في Start Menu)
2. Click **"Create Basic Task"**
3. Name: `Laravel Scheduler`
4. Trigger: **Daily** at 00:00
5. Action: **Start a program**
   - Program: `C:\xampp\php\php.exe`
   - Arguments: `artisan schedule:run`
   - Start in: `D:\xampp\htdocs\msar-backend-12`
6. Click **Finish**
7. Right-click the task → **Properties**
8. In **Triggers** tab, click **Edit**
9. Check **"Repeat task every: 1 minute"**
10. Duration: **Indefinitely**
11. Click **OK**

**Option 2: Command Line (PowerShell as Admin)**

```powershell
schtasks /create /tn "Laravel Scheduler" /tr "C:\xampp\php\php.exe artisan schedule:run" /sc minute /mo 1 /st 00:00
```

---

### Alternative: Queue Worker (If No Cron Access)

إذا **ما تكدر** تضيف Cron Job (مثلاً shared hosting):

**Option 1: Run scheduler manually via API**

Create a route:
```php
// In routes/api.php
Route::get('run-scheduler', function() {
    Artisan::call('schedule:run');
    return ['status' => 'done'];
});
```

Then use external service (like [cron-job.org](https://cron-job.org)) to call:
```
https://your-domain.com/api/run-scheduler
```
Every minute.

**Option 2: Use Queue Worker**

Run this command continuously:
```bash
php artisan queue:work --sleep=60 --tries=3
```

---

### For Local Development/Testing

**You don't need Cron Job** during development. Instead:

**Option 1: Run scheduler manually**
```bash
# Run this command every minute manually
php artisan schedule:run
```

**Option 2: Run backup:auto directly**
```bash
# Run the backup command directly for testing
php artisan backup:auto
```

**Option 3: Use `schedule:work` (Laravel 8+)**
```bash
# This runs the scheduler every minute automatically
php artisan schedule:work
```

Keep this terminal open while testing.

---

### Verification Steps

After setup, verify everything works:

**1. Check scheduler is configured:**
```bash
php artisan schedule:list
```

**2. Enable auto backup in database:**
```bash
php artisan tinker
>>> $s = App\Models\BackupSetting::first();
>>> $s->auto_backup_enabled = true;
>>> $s->auto_backup_interval = 1;  // 1 minute for testing
>>> $s->save();
>>> exit
```

**3. Wait 1 minute, then check logs:**
```bash
tail -f storage/logs/laravel.log
```

You should see:
```
[2025-11-06 10:30:00] local.INFO: AutoBackupCommand: Dispatching RunBackupJob ...
```

**4. Check backup was created:**
```bash
php artisan tinker
>>> App\Models\BackupLog::latest()->first();
```

**5. Check `last_auto_backup_at` was updated:**
```bash
php artisan tinker
>>> App\Models\BackupSetting::first()->last_auto_backup_at;
```

---

### Common Issues

**Issue: Cron job added but scheduler not running**

**Solution:**
```bash
# Check if cron service is running
sudo service cron status

# Restart cron service
sudo service cron restart
```

**Issue: Permission denied**

**Solution:**
```bash
# Give execute permission to artisan
chmod +x artisan

# Check PHP path
which php

# Update crontab with correct PHP path
* * * * * cd /path && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
```

**Issue: Scheduler runs but backup:auto doesn't execute**

**Check:**
1. Is `auto_backup_enabled: true`?
2. Is `enabled: true`?
3. Run manually: `php artisan backup:auto`
4. Check logs: `tail -f storage/logs/laravel.log`

---

### Monitoring

**Check scheduler is running:**
```bash
# View cron logs (Linux)
tail -f /var/log/syslog | grep CRON

# Check Laravel logs
tail -f storage/logs/laravel.log | grep AutoBackupCommand
```

**Check last execution time:**
```bash
php artisan tinker
>>> cache()->get('backup_auto_last_run');
```

---

## Troubleshooting

### Auto backup not running

**Most common reason: Scheduler not configured!**

**Check in order:**

**1. Is Scheduler running?**
```bash
# Check if cron job exists
crontab -l

# Check scheduled tasks
php artisan schedule:list

# Test manually
php artisan schedule:run
```

If NO cron job → Go to **Server Setup** section above and add it.

**2. Is auto backup enabled?**
```bash
php artisan tinker
>>> App\Models\BackupSetting::first()->auto_backup_enabled;
```

Must be `true`.

**3. Is backup system enabled?**
```bash
php artisan tinker
>>> App\Models\BackupSetting::first()->enabled;
```

Must be `true`.

**4. Test the command directly:**
```bash
php artisan backup:auto -v
```

Check the output for errors.

**5. Check logs:**
```bash
tail -f storage/logs/laravel.log | grep AutoBackupCommand
```

**6. Check last execution:**
```bash
php artisan tinker
>>> App\Models\BackupSetting::first()->last_auto_backup_at;
```

If `null`, backup hasn't run yet.

### Interval not working as expected

**Check:**
1. Verify `auto_backup_interval` is in **minutes**, not hours
2. Check `last_auto_backup_at` timestamp in database
3. Calculate time difference: now() - last_auto_backup_at >= interval?

### Backup runs but notifications not sent

**Check:**
1. Is `notify_enabled: true`?
2. Are notification channels enabled? (telegram_enabled, email_enabled, webhook_enabled)
3. Check notification configuration (bot token, chat IDs, etc.)
4. Review `storage/logs/laravel.log` for notification errors

---

## Integration with Existing Features

### Backup Logs
- Auto backups create log entries with `type: "auto"`
- Manual backups have `type: "manual"`
- Both show in the same logs endpoint: `GET /api/backup/logs`

### Notifications
- Auto backups use the same notification system as manual backups
- Respects channel toggles (telegram_enabled, email_enabled, webhook_enabled)
- Sends to all configured recipients (from settings + admins)

### Storage Management
- Auto backups follow the same cleanup rules
- Uses configured retention policies (keep_daily_days, etc.)
- Respects max_storage_mb limit

---

## Future Enhancements (Optional)

Possible improvements for future versions:

1. **Multiple Schedules**: Allow different schedules for DB vs Files
2. **Time Windows**: Only run backups during specific hours
3. **Skip on Success**: Skip auto backup if manual backup was recent
4. **Retry on Failure**: Auto-retry failed backups
5. **Email Summary**: Weekly/monthly backup summary emails
6. **Dashboard Widget**: Show next auto backup time in UI

---

## Version History

### Version 1.0 (2025-11-06)
- Initial implementation of auto backup feature
- Added 4 new database fields
- Created AutoBackupCommand
- Updated Kernel scheduler
- Created frontend documentation

---

## Support

For questions or issues:
1. Check `storage/logs/laravel.log` for errors
2. Review the troubleshooting section above
3. Test manually with `php artisan backup:auto`
4. Verify cron job is running: `crontab -l`

---

## Credits

Developed as part of the MSAR Backup System.
Laravel 12.9.2 | PHP 8.2
