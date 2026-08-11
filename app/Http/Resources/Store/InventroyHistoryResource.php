<?php

namespace App\Http\Resources\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventroyHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'voucherId' => $this->voucherId,
            'itemId' => $this->itemId,
            'itemName' => $this->itemName,
            'stockName' => $this->stockName,
            'description' => $this->description,
            'price' => $this->price / 100,
            'billType' => $this->billType,
            'Employee' => [
                'id' => $this->employeeId,
                'name' => $this->employeeName,
            ],
            'count' => $this->count,
        ];
    }
}
