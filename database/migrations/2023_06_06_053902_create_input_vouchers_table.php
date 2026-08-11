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
        Schema::create('input_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('input_voucher_state_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('number')->nullable();
            $table->foreignId('stock_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->date('date_receive')->default(now());
            $table->date('date')->default(now());
            $table->string('number_bill')->nullable();
            $table->date('date_bill')->default(now());
            $table->text('notes')->nullable();
            $table->string('requested_by')->nullable();
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
        Schema::dropIfExists('input_vouchers');
    }
};
