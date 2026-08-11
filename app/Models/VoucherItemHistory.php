<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class VoucherItemHistory extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function voucher_item_historiable(): MorphTo
    {
        return $this->morphTo();
    }
    public function Item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }
    public function InputVoucherItem(): BelongsTo
    {
        return $this->belongsTo(InputVoucherItem::class, 'input_voucher_item_id', 'id');
    }

    public function Employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
