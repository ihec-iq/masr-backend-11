<?php

namespace App\Http\Resources\Voucher;

use App\Http\Resources\Document\DocumentResource;
use App\Http\Resources\Stock\StockResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InputVoucherResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'numberBill' => $this->number_bill,
            'date' => $this->date,
            'dateReceive' => $this->date_receive,
            'dateBill' => $this->date_bill,
            'notes' => $this->notes,
            'State' => new InputVoucherStateResource($this->State),
            'Stock' => new StockResource($this->Stock),
            'Items' => InputVoucherItemResource::collection($this->Items),
            'requestedBy' => $this->requested_by,
            'itemsCount' => count($this->Items),
            'FilesDocument' => DocumentResource::collection($this->Documents),
        ];
    }
}
