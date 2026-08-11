<?php

namespace App\Observers;

use App\Models\OutputVoucherItem;
use App\Models\InventoryMovement;

class OutputVoucherItemObserver
{
    public function created(OutputVoucherItem $outputVoucherItem): void
    {
        $this->upsert($outputVoucherItem);
    }

    public function updated(OutputVoucherItem $outputVoucherItem): void
    {
        $this->upsert($outputVoucherItem);
    }

    public function deleted(OutputVoucherItem $outputVoucherItem): void
    {
        InventoryMovement::query()
            ->where('source_line_type', OutputVoucherItem::class)
            ->where('source_line_id', $outputVoucherItem->id)
            ->delete();
    }

    public function restored(OutputVoucherItem $outputVoucherItem): void
    {
        $this->upsert($outputVoucherItem);
    }

    private function upsert(OutputVoucherItem $outputVoucherItem): void
    {
        $outputVoucher = $outputVoucherItem->outputVoucher;
        $inputVoucherItem = $outputVoucherItem->inputVoucherItem;
        $inputVoucher = $inputVoucherItem?->inputVoucher;

        if (!$outputVoucher || !$inputVoucherItem || !$inputVoucher) {
            return;
        }

        if (
            $outputVoucher->deleted_at !== null ||
            $inputVoucherItem->deleted_at !== null ||
            $inputVoucher->deleted_at !== null ||
            $outputVoucherItem->deleted_at !== null
        ) {
            return;
        }

        $qty = (int)($outputVoucherItem->count ?? 0);

        InventoryMovement::query()->updateOrCreate(
            [
                'source_line_type' => OutputVoucherItem::class,
                'source_line_id' => $outputVoucherItem->id,
            ],
            [
                'movement_type' => InventoryMovement::TYPE_OUTPUT,

                'item_id' => (int)$outputVoucherItem->item_id,
                'stock_id' => (int)$inputVoucher->stock_id,
                'input_voucher_item_id' => (int)$outputVoucherItem->input_voucher_item_id,

                'movable_type' => get_class($outputVoucher),
                'movable_id' => (int)$outputVoucher->id,

                'quantity' => -$qty,

                'unit_price' => $outputVoucherItem->price !== null ? (int)$outputVoucherItem->price : null,
                'value' => $outputVoucherItem->value !== null ? (int)$outputVoucherItem->value : null,

                'employee_id' => $outputVoucherItem->employee_id ?? $outputVoucher->employee_id,
                'movement_date' => $outputVoucher->date,
                'notes' => $outputVoucherItem->notes,
            ]
        );
    }
}
