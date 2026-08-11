<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('backup_admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('telegram_id')->nullable();
            $table->boolean('active')->default(true);
            $table->json('notify_via'); // ["telegram","email"]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_admins');
    }
};
