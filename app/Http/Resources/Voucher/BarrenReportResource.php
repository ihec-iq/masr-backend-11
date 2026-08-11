<?php

namespace App\Http\Resources\Voucher;

use App\Http\Resources\Employee\EmployeeLiteResource;
use App\Http\Resources\Item\ItemResource;
use App\Http\Resources\Stock\StockResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class BarrenReportResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'OutputId' => $this->OutputId,
            'itemId' => $this->itemId,
            'itemName' => $this->itemName,
            'count' => $this->count,
            'numberOutput' => $this->numberOutput,
            'dateOutput' => $this->dateOutput,
            'price' => $this->price/100,
            'employeeName' => $this->employeeName,
            'employeeId' => $this->employeeId,
        ];
    }
}
