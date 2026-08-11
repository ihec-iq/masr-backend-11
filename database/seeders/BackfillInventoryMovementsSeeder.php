<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use App\Models\InventoryMovement;
use App\Models\InputVoucherItem;
use App\Models\OutputVoucherItem;
use App\Models\RetrievalVoucherItem;
use App\Models\InputVoucher;
use App\Models\OutputVoucher;
use App\Models\RetrievalVoucher;

class BackfillInventoryMovementsSeeder extends Seeder
{
    private int $returnInTypeId = 1;

    public function run(): void
    {
        // Truncate safely in MySQL with FKs
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('inventory_movements')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        DB::transaction(function () {
            $this->backfillInputs();
            $this->backfillOutputs();
            $this->backfillRetrievals();
        });
    }

    private function backfillInputs(): void
    {
        DB::table('input_voucher_items')
            ->join('input_vouchers', 'input_vouchers.id', '=', 'input_voucher_items.input_voucher_id')
            ->whereNull('input_voucher_items.deleted_at')
            ->whereNull('input_vouchers.deleted_at')
            ->orderBy('input_voucher_items.id')
            ->select([
                'input_voucher_items.id as line_id',
                'input_voucher_items.item_id',
                'input_voucher_items.count',
                'input_voucher_items.price',
                'input_voucher_items.value',
                'input_voucher_items.notes',
                'input_vouchers.id as header_id',
                'input_vouchers.stock_id',
                'input_vouchers.date_receive',
                'input_vouchers.date',
            ])
            ->chunkById(1000, function ($rows) {
                $now = now();
                $payload = [];

                foreach ($rows as $row) {
                    $qty = (int)($row->count ?? 0);

                    $payload[] = [
                        'source_line_type' => InputVoucherItem::class,
                        'source_line_id'   => (int)$row->line_id,

                        'movement_type' => InventoryMovement::TYPE_INPUT,
                        'quantity'      => +$qty,

                        'item_id'               => (int)$row->item_id,
                        'stock_id'              => (int)$row->stock_id,
                        'input_voucher_item_id' => (int)$row->line_id,

                        'movable_type' => InputVoucher::class,
                        'movable_id'   => (int)$row->header_id,

                        'unit_price'    => $row->price !== null ? (int)$row->price : null,
                        'value'         => $row->value !== null ? (int)$row->value : null,
                        'employee_id'   => null,
                        'movement_date' => $row->date_receive ?? $row->date,
                        'notes'         => $row->notes,

                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                $this->upsertMovements($payload);
            }, 'input_voucher_items.id', 'line_id');
    }

    private function backfillOutputs(): void
    {
        DB::table('output_voucher_items')
            ->join('output_vouchers', 'output_vouchers.id', '=', 'output_voucher_items.output_voucher_id')
            ->join('input_voucher_items', 'input_voucher_items.id', '=', 'output_voucher_items.input_voucher_item_id')
            ->join('input_vouchers', 'input_vouchers.id', '=', 'input_voucher_items.input_voucher_id')
            ->whereNull('output_voucher_items.deleted_at')
            ->whereNull('output_vouchers.deleted_at')
            ->whereNull('input_voucher_items.deleted_at')
            ->whereNull('input_vouchers.deleted_at')
            ->orderBy('output_voucher_items.id')
            ->select([
                'output_voucher_items.id as line_id',
                'output_voucher_items.item_id',
                'output_voucher_items.count',
                'output_voucher_items.price',
                'output_voucher_items.value',
                'output_voucher_items.notes',
                'output_voucher_items.employee_id as line_employee_id',
                'output_voucher_items.input_voucher_item_id',

                'output_vouchers.id as header_id',
                'output_vouchers.date as movement_date',
                'output_vouchers.employee_id as header_employee_id',

                'input_vouchers.stock_id as stock_id',
            ])
            ->chunkById(1000, function ($rows) {
                $now = now();
                $payload = [];

                foreach ($rows as $row) {
                    $qty = (int)($row->count ?? 0);

                    $employeeId = $row->line_employee_id !== null
                        ? (int)$row->line_employee_id
                        : ($row->header_employee_id !== null ? (int)$row->header_employee_id : null);

                    $payload[] = [
                        'source_line_type' => OutputVoucherItem::class,
                        'source_line_id'   => (int)$row->line_id,

                        'movement_type' => InventoryMovement::TYPE_OUTPUT,
                        'quantity'      => -$qty,

                        'item_id'               => (int)$row->item_id,
                        'stock_id'              => (int)$row->stock_id,
                        'input_voucher_item_id' => (int)$row->input_voucher_item_id,

                        'movable_type' => OutputVoucher::class,
                        'movable_id'   => (int)$row->header_id,

                        'unit_price'    => $row->price !== null ? (int)$row->price : null,
                        'value'         => $row->value !== null ? (int)$row->value : null,
                        'employee_id'   => $employeeId,
                        'movement_date' => $row->movement_date,
                        'notes'         => $row->notes,

                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                $this->upsertMovements($payload);
            }, 'output_voucher_items.id', 'line_id'); // ✅ FIX: alias
    }

    private function backfillRetrievals(): void
    {
        DB::table('retrieval_voucher_items')
            ->join('retrieval_vouchers', 'retrieval_vouchers.id', '=', 'retrieval_voucher_items.retrieval_voucher_id')
            ->join('input_voucher_items', 'input_voucher_items.id', '=', 'retrieval_voucher_items.input_voucher_item_id')
            ->join('input_vouchers', 'input_vouchers.id', '=', 'input_voucher_items.input_voucher_id')
            ->whereNull('retrieval_voucher_items.deleted_at')
            ->whereNull('retrieval_vouchers.deleted_at')
            ->whereNull('input_voucher_items.deleted_at')
            ->whereNull('input_vouchers.deleted_at')
            ->orderBy('retrieval_voucher_items.id')
            ->select([
                'retrieval_voucher_items.id as line_id',
                'retrieval_voucher_items.item_id',
                'retrieval_voucher_items.count',
                'retrieval_voucher_items.price',
                'retrieval_voucher_items.value',
                'retrieval_voucher_items.notes',
                'retrieval_voucher_items.employee_id as line_employee_id',
                'retrieval_voucher_items.input_voucher_item_id',
                'retrieval_voucher_items.retrieval_voucher_item_type_id as type_id',

                'retrieval_vouchers.id as header_id',
                'retrieval_vouchers.date as movement_date',
                'retrieval_vouchers.employee_id as header_employee_id',

                'input_vouchers.stock_id as stock_id',
            ])
            ->chunkById(1000, function ($rows) {
                $now = now();
                $payload = [];

                foreach ($rows as $row) {
                    $qty = (int)($row->count ?? 0);
                    $isReturnIn = ((int)$row->type_id === $this->returnInTypeId);

                    $employeeId = $row->line_employee_id !== null
                        ? (int)$row->line_employee_id
                        : ($row->header_employee_id !== null ? (int)$row->header_employee_id : null);

                    $payload[] = [
                        'source_line_type' => RetrievalVoucherItem::class,
                        'source_line_id'   => (int)$row->line_id,

                        'movement_type' => $isReturnIn
                            ? InventoryMovement::TYPE_RETURN_IN
                            : InventoryMovement::TYPE_RETURN_OUT,

                        'quantity' => $isReturnIn ? +$qty : -$qty,

                        'item_id'               => (int)$row->item_id,
                        'stock_id'              => (int)$row->stock_id,
                        'input_voucher_item_id' => (int)$row->input_voucher_item_id,

                        'movable_type' => RetrievalVoucher::class,
                        'movable_id'   => (int)$row->header_id,

                        'unit_price'    => $row->price !== null ? (int)$row->price : null,
                        'value'         => $row->value !== null ? (int)$row->value : null,
                        'employee_id'   => $employeeId,
                        'movement_date' => $row->movement_date,
                        'notes'         => $row->notes,

                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                $this->upsertMovements($payload);
            }, 'retrieval_voucher_items.id', 'line_id'); // ✅ FIX: alias
    }

    private function upsertMovements(array $payload): void
    {
        if (empty($payload)) {
            return;
        }

        DB::table('inventory_movements')->upsert(
            $payload,
            ['source_line_type', 'source_line_id'],
            [
                'movement_type',
                'quantity',
                'item_id',
                'stock_id',
                'input_voucher_item_id',
                'movable_type',
                'movable_id',
                'unit_price',
                'value',
                'employee_id',
                'movement_date',
                'notes',
                'updated_at',
            ]
        );
    }
}
