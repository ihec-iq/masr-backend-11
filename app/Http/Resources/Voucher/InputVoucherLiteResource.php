<?php

namespace App\Http\Resources\Voucher;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InputVoucherLiteResource extends JsonResource
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
            'date' => $this->date,
            'notes' => $this->notes,
            'state' => new InputVoucherStateResource($this->State),
            //'items' => InputVoucherItemResource::collection($this->Items),
            'itemsCount' => (int) $this->items_count ,
            'documentsCount' => (int) $this->documents_count, 
            'requestedBy' => $this->requested_by,
        ];
    }
}
