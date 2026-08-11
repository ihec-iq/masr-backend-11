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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('item_category_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('measuring_unit')->nullable();
            $table->foreignId('user_create_id')->constrained(table: 'users', indexName: 'idCreateItem')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignId('user_update_id')->constrained(table: 'users', indexName: 'idUpdateItem')->onUpdate('cascade')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
