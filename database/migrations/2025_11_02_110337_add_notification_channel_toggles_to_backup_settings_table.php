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
            // إضافة خيارات تفعيل/تعطيل منفصلة لكل قناة إشعار
            $table->boolean('telegram_enabled')->default(true)->after('notify_on');
            $table->boolean('email_enabled')->default(true)->after('telegram_enabled');
            $table->boolean('webhook_enabled')->default(false)->after('email_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backup_settings', function (Blueprint $table) {
            $table->dropColumn(['telegram_enabled', 'email_enabled', 'webhook_enabled']);
        });
    }
};
