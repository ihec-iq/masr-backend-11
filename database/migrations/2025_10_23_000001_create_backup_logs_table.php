<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * إنشاء جدول سجل النسخ الاحتياطي
     */
    public function up(): void
    {
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();

            // نوع العملية
            $table->enum('type', ['auto', 'manual', 'restore'])->default('auto');

            // استهداف النسخ
            $table->boolean('include_files')->default(false);
            $table->json('databases')->nullable(); // قائمة القواعد التي تم نسخها
            $table->json('files')->nullable();     // قائمة الملفات أو المجلدات

            // حالة العملية
            $table->enum('status', ['running', 'success', 'failed' , 'pending'])->default('running');
            $table->text('message')->nullable(); // في حال وجود خطأ أو ملاحظة

            // نتائج النسخ
            $table->string('storage_disk')->default('google');
            $table->json('backup_paths')->nullable(); // المسارات الفعلية للنسخ على Google Drive
            $table->json('checksums')->nullable();    // قائمة sha256 للملفات
            $table->unsignedBigInteger('total_size')->default(0); // الحجم بالبايت

            // سجل التوقيتات
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            // علاقة بالإعدادات
            $table->foreignId('backup_setting_id')->nullable()->constrained('backup_settings')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
    }
};
