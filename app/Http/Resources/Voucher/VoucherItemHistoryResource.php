<?php

namespace App\Http\Resources\Voucher;

use App\Http\Resources\Employee\EmployeeLiteResource;
use App\Http\Resources\Item\ItemResource;
use App\Http\Resources\Stock\StockResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class VoucherItemHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $item = null;
        $type = "";
        //Log::alert(message: $this->voucher_item_historiable->toArray());
        if ($this->voucher_item_historiable_type == 'App\\Models\\InputVoucherItem') {
            //$item = new InputVoucherItemResource($this->voucher_item_historiable);
            $type = 'سند ادخال';
            $item = [
                'idVoucher' => $this->voucher_item_historiable->InputVoucher->id,
                'date' => $this->voucher_item_historiable->InputVoucher->date,
                'Item' => new ItemResource($this->voucher_item_historiable->Item),
                'Stock' => new StockResource($this->voucher_item_historiable->Stock),
                'description' => $this->voucher_item_historiable->description,
            ];
        } elseif ($this->voucher_item_historiable_type == 'App\\Models\\OutputVoucherItem') {
            //$item = new OutputVoucherItemResource($this->voucher_item_historiable);
            $type = 'سند اصدار';
            $item = [
                'idVoucher' => $this->voucher_item_historiable->OutputVoucher->id,
                'date' => $this->voucher_item_historiable->OutputVoucher->date,
                'Item' => new ItemResource($this->voucher_item_historiable->inputVoucherItem->Item),
                'Stock' => new StockResource($this->voucher_item_historiable->inputVoucherItem->Stock),
                'description' => $this->voucher_item_historiable->inputVoucherItem->description,
            ];
        } elseif ($this->voucher_item_historiable_type == 'App\\Models\\RetrievalVoucherItem') {
            //$item = new OutputVoucherItemResource($this->voucher_item_historiable);
            $type = 'سند ارجاع';
            $item = [
                'idVoucher' => $this->voucher_item_historiable->RetrievalVoucher->id,
                'date' => $this->voucher_item_historiable->Voucher->date,
                'Item' => new ItemResource($this->voucher_item_historiable->Item),
                'Stock' => new StockResource($this->voucher_item_historiable->inputVoucherItem->Stock),
                'description' => $this->voucher_item_historiable->Item->description,
            ];
        }

        return [
            'id' => $this->id,
            'type' => $type,
            'inputVoucherItemId' => $this->input_voucher_item_id,
            'voucherItemHistoriableId' => $this->voucher_item_historiable_id,
            'voucherItemHistoriableType' => $this->voucher_item_historiable_type,
            'price' => $this->price,
            'count' => $this->count * -1,
            'notes' => $this->notes,
            'Employee' => new EmployeeLiteResource($this->Employee),
            'Voucher' => $item,
        ];
    }
}
