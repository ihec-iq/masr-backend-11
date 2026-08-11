<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();

            // Item & Stock
            $table->foreignId('item_id')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreignId('stock_id')
                ->constrained('stocks')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            // Batch trace (optional)
            $table->foreignId('input_voucher_item_id')
                ->nullable()
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');

            // Source document (Morph header)
            $table->unsignedBigInteger('movable_id');
            $table->string('movable_type');

            /**
             * Source line (exact row reference)
             * مثال:
             * source_line_type = App\Models\OutputVoucherItem
             * source_line_id   = output_voucher_items.id
             */
            $table->unsignedBigInteger('source_line_id')->nullable();
            $table->string('source_line_type')->nullable();

            // Movement type
            $table->string('movement_type', 50);

            // Signed quantity (+ / -)
            $table->integer('quantity');

            // Optional costing
            $table->bigInteger('unit_price')->nullable();
            $table->bigInteger('value')->nullable();

            // Actor (optional)
            $table->foreignId('employee_id')
                ->nullable()
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');

            // Date
            $table->date('movement_date');

            $table->text('notes')->nullable();

            $table->timestamps();

            // =========================
            // Indexes (clear names)
            // =========================

            $table->index(
                ['stock_id', 'item_id'],
                'inventory_movements_stock_item_index'
            );

            $table->index(
                ['input_voucher_item_id'],
                'inventory_movements_input_voucher_item_index'
            );

            $table->index(
                ['movable_type', 'movable_id'],
                'inventory_movements_morph_reference_index'
            );

            $table->index(
                ['source_line_type', 'source_line_id'],
                'inventory_movements_source_line_index'
            );

            $table->index(
                ['movement_date'],
                'inventory_movements_movement_date_index'
            );

            $table->index(
                ['movement_type'],
                'inventory_movements_movement_type_index'
            );

            // Optional (strongly recommended): prevent duplicates per source line
            $table->unique(
                ['source_line_type', 'source_line_id'],
                'inventory_movements_source_line_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
