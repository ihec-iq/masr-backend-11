<?php

namespace App\Http\Resources\Inventory;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'stocId'    => $this->stock_id,
            'stockName'  => $this->stock_name,
            'itemId'     => $this->item_id,
            'itemName'   => $this->item_name,
            'price'  => $this->unit_price/100,
            'balance'     => (int) $this->balance,
        ];
    }
}
