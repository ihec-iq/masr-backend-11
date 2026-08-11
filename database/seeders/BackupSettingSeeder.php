<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BackupSetting;

class BackupSettingSeeder extends Seeder
{
    public function run(): void
    {
        BackupSetting::updateOrCreate(
            ['id' => 1],
            [
                // 🔧 التحكم العام
                'include_files' => true,
                'multi_db' => false,
                'disk' => 'local', // لاحقًا ممكن تغيّر إلى 'google'
                'timezone' => 'Asia/Baghdad',
                'cron' => '0 15 * * *', // يوميًا الساعة 3 مساءً

                // 🔁 سياسات الاحتفاظ
                'keep_daily_days' => 7,
                'keep_weekly_weeks' => 4,
                'keep_monthly_months' => 6,
                'keep_yearly_years' => 10,
                'max_storage_mb' => 50_000, // 50GB

                // 🔔 الإشعارات
                'notify_enabled' => true,
                'notify_on' => 'failure', // success | failure | both
                'telegram_bot_token' => env('TELEGRAM_BOT_TOKEN', ''),

                // 📧 البريد (افتراضيًا فارغ – نضبطه لاحقًا)
                'emails' => 'admin@example.com',

                // 🌐 Webhook (افتراضيًا فارغ)
                'webhook_urls' => null,
                'webhook_secret' => null,

                // ⚙️ روابط التحميل الموقّتة
                'temp_link_expiry' => 60, // بالدقائق
                'last_run_at' => null,
            ]
        );
    }
}
