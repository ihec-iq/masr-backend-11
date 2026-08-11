<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class InputVoucherItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function InputVoucher()
    {
        return $this->belongsTo(InputVoucher::class, 'input_voucher_id', 'id');
    }

    public function Item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function Histories(): MorphMany
    {
        return $this->morphMany(VoucherItemHistory::class, 'voucher_item_historiable');
    }

    public function OutputVoucherItems(): HasMany
    {
        return $this->hasMany(OutputVoucherItem::class);
    }
}
