<?php

namespace App\Http\Resources\Voucher;

use App\Http\Resources\Employee\EmployeeLiteResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RetrievalVoucherResource extends JsonResource
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
            'Items' => RetrievalVoucherItemResource::collection($this->Items), 
            'Employee' => new EmployeeLiteResource($this->Employee),
            'Type' => new RetrievalVoucherItemTypeResource($this->Type),
            'TypeId' => $this->Type->id,
            'itemsCount' => count($this->Items),
        ];
    }
}
