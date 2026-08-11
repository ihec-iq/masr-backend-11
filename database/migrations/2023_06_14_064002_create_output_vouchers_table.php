<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //php artisan migrate:refresh --path=database/migrations/2023_06_14_064002_create_output_vouchers_table.php

        Schema::create('output_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onUpdate('cascade')->onDelete('cascade');
            $table->string('number')->nullable();
            $table->date('date');
            $table->string('number_bill')->nullable();
            $table->date('date_bill')->default(now());
            $table->text('notes')->nullable();
            $table->foreignId('user_create_id')->constrained(table: 'users')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('user_update_id')->constrained(table: 'users')->onUpdate('cascade')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('output_vouchers');
    }
};
