<?php

namespace App\Http\Resources\Voucher;

use App\Http\Resources\Document\DocumentResource;
use App\Http\Resources\Employee\EmployeeBigLiteResource;
use App\Http\Resources\Stock\StockResource;
use App\Http\Resources\User\UserLiteResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OutputVoucherResource extends JsonResource
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
            'numberBill'=> $this->number_bill,
            'dateBill'=> $this->date_bill,
            'Items' => OutputVoucherItemResource::collection($this->Items),
            'Employee' => new EmployeeBigLiteResource($this->Employee),
            'Stock' => new StockResource($this->Stock),
            'itemsCount' => count($this->Items),
            'FilesDocument' => DocumentResource::collection($this->Documents),
            'UserCreated' => new UserLiteResource($this->UserCreated),
        ];
    }
}
