<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('backup_settings', function (Blueprint $table) {
            // تفعيل/إلغاء النسخ التلقائي
            $table->boolean('auto_backup_enabled')->default(false);

            // الفترة الزمنية (interval) - بالدقائق
            // مثال: 60 = كل ساعة، 1440 = كل يوم، 10080 = كل أسبوع
            $table->integer('auto_backup_interval')->default(1440)->after('auto_backup_enabled');

            // نوع النسخة التلقائية: db, files, both
            $table->enum('auto_backup_type', ['db', 'files', 'both'])->default('both')->after('auto_backup_interval');

            // آخر تشغيل تلقائي
            $table->timestamp('last_auto_backup_at')->nullable()->after('auto_backup_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backup_settings', function (Blueprint $table) {
            $table->dropColumn([
                'auto_backup_enabled',
                'auto_backup_interval',
                'auto_backup_type',
                'last_auto_backup_at'
            ]);
        });
    }
};
