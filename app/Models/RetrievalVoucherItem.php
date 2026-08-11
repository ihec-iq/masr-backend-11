<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class RetrievalVoucherItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $fillable = ['output_voucher_item_id'];

    public function RetrievalVoucher(): BelongsTo
    {
        return $this->belongsTo(RetrievalVoucher::class, 'retrieval_voucher_id', 'id');
    }

    public function InputVoucherItem(): BelongsTo
    {
        return $this->belongsTo(InputVoucherItem::class, 'input_voucher_item_id', 'id');
    }
    public function Type(): BelongsTo
    {
        return $this->belongsTo(RetrievalVoucherItemType::class,"retrieval_voucher_item_type_id","id");
    }
    public function Employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function Item(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function Histories(): MorphMany
    {
        return $this->morphMany(VoucherItemHistory::class, 'voucher_item_historiable');
    }
}
