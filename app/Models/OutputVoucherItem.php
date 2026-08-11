<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class OutputVoucherItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function OutputVoucher(): BelongsTo
    {
        return $this->belongsTo(OutputVoucher::class, 'output_voucher_id', 'id');
    }

    public function Item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function InputVoucherItem(): BelongsTo
    {
        return $this->belongsTo(InputVoucherItem::class, 'input_voucher_item_id', 'id');
    }

    public function Histories(): MorphMany
    {
        return $this->morphMany(VoucherItemHistory::class, 'voucher_item_historiable');
    }
}
