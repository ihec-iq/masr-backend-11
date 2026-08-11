<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemStoreView extends Model
{
    protected $table = 'item_store_views';

    use HasFactory;
    public function generateFingerprint(): string
    {
         
        // IMPORTANT: Select the columns that define the row's data.
        // Do not include the primary key, or timestamps like created_at/updated_at.
        $dataToHash = [
            $this->itemId,
            $this->code,
            $this->price,
            $this->ItemDescription,
            $this->itemCategoryId,
            $this->stockId
            // Add any other columns that matter
        ];

        // Using hash() is DB-agnostic. sha256 is the algorithm name.
        return hash('sha256', implode('||', $dataToHash));
    }
}
