<?php

namespace App\Models;

use App\Http\Controllers\Api\v1\HrDocumentController;
use App\Http\Resources\Bonus\BonusDegreeStageResource;
use App\Http\Resources\Bonus\BonusResource;
use App\Http\Resources\Bonus\BonusWithoutEmployeeResource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Facades\Log;

class Employee extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function InputVouchers(): HasMany
    {
        return $this->hasMany(InputVoucher::class);
    }
    public function HrDocuments(): HasMany
    {
        return $this->hasMany(HrDocument::class);
    }
    public function getDifNextDateAttribute()
    {
        return now()->diffInDays($this->date_next_bonus);
    }

    public function Section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }
    public function MoveSection(): BelongsTo
    {
        return $this->belongsTo(Section::class, "move_section_id", "id");
    }
    public function EmployeeCenter(): BelongsTo
    {
        return $this->belongsTo(EmployeeCenter::class);
    }
    public function EmployeePosition(): BelongsTo
    {
        return $this->belongsTo(EmployeePosition::class);
    }
    public function EmployeeType(): BelongsTo
    {
        return $this->belongsTo(EmployeeType::class);
    }
    public function User(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function Vacation(): HasOne
    {
        return $this->hasOne(Vacation::class);
    }

    public function VacationDailies(): HasManyThrough
    {
        return $this->hasManyThrough(VacationDaily::class, Vacation::class);
    }

    public function VacationTimes(): HasManyThrough
    {
        return $this->hasManyThrough(VacationTime::class, Vacation::class);
    }

    public function VacationSicks(): HasManyThrough
    {
        return $this->hasManyThrough(VacationSick::class, Vacation::class);
    }

    public function OutputVouchers(): HasMany
    {
        return $this->hasMany(OutputVoucher::class);
    }
    public function BonusJobTitle(): BelongsTo
    {
        return $this->belongsTo(BonusJobTitle::class, 'bonus_job_title_id');
    }
    public function Study(): BelongsTo
    {
        return $this->belongsTo(Study::class, 'study_id');
    }
    public function Certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class, 'certificate_id');
    }
    public function DegreeStage(): BelongsTo
    {
        return $this->belongsTo(BonusDegreeStage::class, 'degree_stage_id');
    }
    public function Bonus(): HasMany
    {
        return $this->hasMany(Bonus::class);
    }
    public function LastBonus(): HasOne
    {
        return $this->hasOne(Bonus::class)->latestOfMany();
    }
    public function getGetBonusEmployeeTotalAttribute($attraction = 4)
    {
        $hrDocument = new HrDocumentController();
        return $hrDocument->check_bonus_employee_total(request()->merge(['attraction' => $attraction]), $this->id);
    }
    public function getNextDegreeStageAttribute(): ?BonusDegreeStageResource
    {
        return new BonusDegreeStageResource(BonusDegreeStage::find($this->degree_stage_id + 1));
    }


    public function getNextNoteAttribute()
    {
        $hrDocument = new HrDocumentController();
        $results = $hrDocument->check_bonus_employee($this->id);
        $result = "";
        //$result = implode(', ', $results->Documents);
        foreach ($results['Documents'] as $key => $value) {
            $result .= $value['title'] . "(" . $value['number'] . " في " . $value['issue_date'] . ") ";
        }
        return  $result;
    }


    public function LastPromotion(): HasOne
    {
        return $this->hasOne(Promotion::class)->latestOfMany();
    }
    public function UserUpdate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function UserCreate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
