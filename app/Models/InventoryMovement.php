<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class InventoryMovement extends Model
{
    protected $table = 'inventory_movements';

    public const TYPE_INPUT      = 'INPUT';
    public const TYPE_OUTPUT     = 'OUTPUT';
    public const TYPE_RETURN_IN  = 'RETURN_IN';
    public const TYPE_RETURN_OUT = 'RETURN_OUT';

    protected $fillable = [
        'item_id',
        'stock_id',
        'input_voucher_item_id',

        'movable_id',
        'movable_type',

        'source_line_id',
        'source_line_type',

        'movement_type',
        'quantity',

        'unit_price',
        'value',

        'employee_id',
        'movement_date',
        'notes',
    ];

    protected $casts = [
        'item_id' => 'integer',
        'stock_id' => 'integer',
        'input_voucher_item_id' => 'integer',
        'movable_id' => 'integer',
        'source_line_id' => 'integer',
        'employee_id' => 'integer',
        'quantity' => 'integer',
        'unit_price' => 'integer',
        'value' => 'integer',
        'movement_date' => 'date',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function movable(): MorphTo
    {
        return $this->morphTo();
    }
}
