<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * إنشاء جدول إعدادات النسخ الاحتياطي
     */
    public function up(): void
    {
        Schema::create('backup_settings', function (Blueprint $table) {
            $table->id();

            // ===== التشغيل العام =====
            $table->string('cron')->default('0 15 * * *');           // الجدولة الافتراضية (3PM بغداد)
            $table->string('timezone')->default('Asia/Baghdad');     // المنطقة الزمنية
            $table->unsignedBigInteger('max_storage_mb')->default(51200); // 50GB كحد افتراضي
            // ===== النطاق =====
            $table->boolean('include_files')->default(false);        // هل نضمّن الملفات؟
            $table->json('include_paths')->nullable();               // مسارات الملفات المطلوب نسخها
            $table->json('exclude_paths')->nullable();               // مسارات الملفات المستثناة
            $table->boolean('multi_db')->default(false);             // دعم تعدد القواعد
            $table->json('selected_databases')->nullable();          // القواعد المحددة للنسخ

            // ===== الاحتفاظ (Retention) =====
            $table->unsignedInteger('keep_daily_days')->default(16);
            $table->unsignedInteger('keep_weekly_weeks')->default(8);
            $table->unsignedInteger('keep_monthly_months')->default(12);
            $table->unsignedInteger('keep_yearly_years')->default(10);

            // ===== التخزين =====
            $table->string('disk')->default('google');               // التخزين الافتراضي
            $table->string('drive_folder')->default('Backups');      // مجلد الجذر في Google Drive
            $table->unsignedInteger('temp_link_expiry')->default(60); // صلاحية الروابط بالدقائق

            // ===== التحقق =====
            $table->boolean('checksum_enabled')->default(true);      // التحقق من سلامة الملفات

            // ===== الإشعارات =====
            $table->boolean('notify_enabled')->default(true);
            $table->string('notify_on')->default('failure');         // success | failure | both
            $table->text('emails')->nullable();                      // قائمة الإيميلات مفصولة بفاصلة
            $table->string('telegram_bot_token')->nullable();        // توكن بوت تيليجرام
            $table->text('telegram_chat_ids')->nullable();           // قائمة معرفات الدردشات
            $table->text('webhook_urls')->nullable();                // روابط Webhook
            $table->string('webhook_secret')->nullable();            // مفتاح توقيع HMAC
            $table->unsignedInteger('stale_hours')->default(48);     // عدد الساعات قبل التنبيه Stale

            // ===== التواريخ =====
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_settings');
    }
};
