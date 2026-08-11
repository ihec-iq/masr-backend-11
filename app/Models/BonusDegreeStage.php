<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonusDegreeStage extends Model
{
    use HasFactory;
    protected $guarded = [];
    public function Degree(): BelongsTo
    {
        return $this->belongsTo(BonusDegree::class, 'bonus_degree_id');
    }
    public function Stage(): BelongsTo
    {
        return $this->belongsTo(BonusStage::class, 'bonus_stage_id');
    }
    public function getTitleAttribute(): string
    {
        return 'الدرجة ' . $this->Degree->name . ' المرحلة ' . $this->Stage->name;
    }
}
