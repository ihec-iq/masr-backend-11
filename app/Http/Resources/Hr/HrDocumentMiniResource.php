<?php

namespace App\Http\Resources\Hr;

use App\Http\Resources\Document\DocumentHrBotResource;
use App\Http\Resources\Document\DocumentResource;
use App\Http\Resources\Employee\EmployeeBigLiteResource;
use App\Http\Resources\Employee\EmployeeLiteResource;
use App\Http\Resources\User\UserLiteResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HrDocumentMiniResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'number' => $this->number,
            'date' => $this->issue_date,
            'addDays' => $this->add_days,
            'addMonths' => $this->add_months,
            'issueDate' => $this->issue_date,
            'isActive' => $this->is_active,
        ];
    }
}
