<?php

namespace App\Http\Resources\Employee;

use App\Http\Resources\Bonus\BonusDegreeStageResource;
use App\Http\Resources\Bonus\BonusWithoutEmployeeResource;
use App\Http\Resources\GeneralIdNameResource;
use App\Http\Resources\User\SectionResource;
use App\Http\Resources\User\UserLiteResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeBonusTotalResource extends JsonResource
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
            'checked' => 0,
            'name' => $this->name,
            'dateWork' => $this->date_work,
            'current' => [
                'number' => $this->number_last_bonus,
                'dateBonus' => $this->date_last_bonus,
                'numberPromotion' => $this->number_last_promotion,
                'datePromotion' => $this->date_last_promotion,
                'degreeStage' => 'الدرجة ' . $this->DegreeStage->Degree->name . ' المرحلة ' . $this->DegreeStage->Stage->name,
                'stage' =>   $this->DegreeStage->Stage->name,
                'degree' =>   $this->DegreeStage->Degree->name,
                'salary' => $this->DegreeStage->salary,
                'notes' => $lastBonus->notes ?? "",
                'difBonusDate' => $this->getDifNextBonusDateAttribute(),
            ],
            'position' => $this->EmployeePosition->name,
            'center' => $this->EmployeeCenter->name,
            'section' => $this->Section->name,
            'department' => $this->Section->Department->name,
            'type' => $this->EmployeeType->name,
            'jobTitle' => $this->BonusJobTitle->name,
            'study' => $this->Study->name,
            'certificate' => $this->Certificate->name,
            'notes' => '',
            'LastBonus' => new BonusWithoutEmployeeResource($this->Bonus->last()) ?? "",
            'getBonusEmployeeTotal' => $this->getBonusEmployeeTotal,

        ];
    }

    public function getDifNextBonusDateAttribute(): int
    {
        // Assuming you want to calculate the difference in days
        return now()->diffInDays($this->date_next_bonus);
    }
    public function getDifNextPromotionDateAttribute(): int
    {
        // Assuming you want to calculate the difference in days
        return now()->diffInDays($this->date_next_promotion);
    }
}
