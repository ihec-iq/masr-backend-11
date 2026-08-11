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
        Schema::table('backup_admins', function (Blueprint $table) {
            // إضافة حقل webhook_url - يدعم عدة URLs مفصولة بفاصلة
            $table->text('webhook_url')->nullable()->after('telegram_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('backup_admins', function (Blueprint $table) {
            $table->dropColumn('webhook_url');
        });
    }
};
