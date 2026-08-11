<?php

namespace App\Observers;

use App\Models\InputVoucherItem;
use App\Models\InventoryMovement;

class InputVoucherItemObserver
{
    public function created(InputVoucherItem $inputVoucherItem): void
    {
        $this->upsert($inputVoucherItem);
    }

    public function updated(InputVoucherItem $inputVoucherItem): void
    {
        $this->upsert($inputVoucherItem);
    }

    public function deleted(InputVoucherItem $inputVoucherItem): void
    {
        InventoryMovement::query()
            ->where('source_line_type', InputVoucherItem::class)
            ->where('source_line_id', $inputVoucherItem->id)
            ->delete();
    }

    public function restored(InputVoucherItem $inputVoucherItem): void
    {
        $this->upsert($inputVoucherItem);
    }

    private function upsert(InputVoucherItem $inputVoucherItem): void
    {
        $inputVoucher = $inputVoucherItem->inputVoucher;

        if (!$inputVoucher || $inputVoucher->deleted_at !== null) {
            return;
        }

        if ($inputVoucherItem->deleted_at !== null) {
            return;
        }

        $qty = (int)($inputVoucherItem->count ?? 0);

        InventoryMovement::query()->updateOrCreate(
            [
                'source_line_type' => InputVoucherItem::class,
                'source_line_id' => $inputVoucherItem->id,
            ],
            [
                'movement_type' => InventoryMovement::TYPE_INPUT,

                'item_id' => (int)$inputVoucherItem->item_id,
                'stock_id' => (int)$inputVoucher->stock_id,
                'input_voucher_item_id' => (int)$inputVoucherItem->id,

                'movable_type' => get_class($inputVoucher),
                'movable_id' => (int)$inputVoucher->id,

                'quantity' => +$qty,

                'unit_price' => $inputVoucherItem->price !== null ? (int)$inputVoucherItem->price : null,
                'value' => $inputVoucherItem->value !== null ? (int)$inputVoucherItem->value : null,

                'employee_id' => null,
                'movement_date' => $inputVoucher->date_receive ?? $inputVoucher->date,
                'notes' => $inputVoucherItem->notes,
            ]
        );
    }
}
