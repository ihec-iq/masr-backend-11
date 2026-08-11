<?php

namespace App\Observers;

use App\Models\RetrievalVoucherItem;
use App\Models\InventoryMovement;

class RetrievalVoucherItemObserver
{
    private int $returnInTypeId = 1;

    public function created(RetrievalVoucherItem $retrievalVoucherItem): void
    {
        $this->upsert($retrievalVoucherItem);
    }

    public function updated(RetrievalVoucherItem $retrievalVoucherItem): void
    {
        $this->upsert($retrievalVoucherItem);
    }

    public function deleted(RetrievalVoucherItem $retrievalVoucherItem): void
    {
        InventoryMovement::query()
            ->where('source_line_type', RetrievalVoucherItem::class)
            ->where('source_line_id', $retrievalVoucherItem->id)
            ->delete();
    }

    public function restored(RetrievalVoucherItem $retrievalVoucherItem): void
    {
        $this->upsert($retrievalVoucherItem);
    }

    private function upsert(RetrievalVoucherItem $retrievalVoucherItem): void
    {
        $retrievalVoucher = $retrievalVoucherItem->retrievalVoucher;

        $inputVoucherItem = $retrievalVoucherItem->inputVoucherItem;
        $inputVoucher = $inputVoucherItem?->inputVoucher;

        if (!$retrievalVoucher || !$inputVoucherItem || !$inputVoucher) {
            return;
        }

        if (
            $retrievalVoucher->deleted_at !== null ||
            $retrievalVoucherItem->deleted_at !== null ||
            $inputVoucherItem->deleted_at !== null ||
            $inputVoucher->deleted_at !== null
        ) {
            return;
        }

        $qty = (int)($retrievalVoucherItem->count ?? 0);

        $isReturnIn = ((int)$retrievalVoucherItem->retrieval_voucher_item_type_id === $this->returnInTypeId);

        $movementType = $isReturnIn
            ? InventoryMovement::TYPE_RETURN_IN
            : InventoryMovement::TYPE_RETURN_OUT;

        $signedQty = $isReturnIn ? +$qty : -$qty;

        InventoryMovement::query()->updateOrCreate(
            [
                'source_line_type' => RetrievalVoucherItem::class,
                'source_line_id' => $retrievalVoucherItem->id,
            ],
            [
                'movement_type' => $movementType,

                'item_id' => (int)$retrievalVoucherItem->item_id,
                'stock_id' => (int)$inputVoucher->stock_id,
                'input_voucher_item_id' => (int)$retrievalVoucherItem->input_voucher_item_id,

                'movable_type' => get_class($retrievalVoucher),
                'movable_id' => (int)$retrievalVoucher->id,

                'quantity' => $signedQty,

                'unit_price' => $retrievalVoucherItem->price !== null ? (int)$retrievalVoucherItem->price : null,
                'value' => $retrievalVoucherItem->value !== null ? (int)$retrievalVoucherItem->value : null,

                'employee_id' => $retrievalVoucherItem->employee_id ?? $retrievalVoucher->employee_id,
                'movement_date' => $retrievalVoucher->date,
                'notes' => $retrievalVoucherItem->notes,
            ]
        );
    }
}
