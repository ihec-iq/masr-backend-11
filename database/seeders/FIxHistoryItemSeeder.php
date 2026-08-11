<?php

namespace Database\Seeders;

use App\Models\InputVoucherItem;
use App\Models\OutputVoucherItem;
use App\Models\VoucherItemHistory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FixHistoryItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        VoucherItemHistory::truncate();
        $input_voucher_items = InputVoucherItem::get();
        foreach ($input_voucher_items as $input_voucher_item) {
            VoucherItemHistory::create([
                'input_voucher_item_id' => $input_voucher_item->id,
                'item_id' => $input_voucher_item->item_id,
                'employee_id' => 1,
                'voucher_item_historiable_id' => $input_voucher_item->id,
                'voucher_item_historiable_type' => 'App\Models\InputVoucherItem',
                'price' => $input_voucher_item->price,
                'count' => $input_voucher_item->count,
                'notes' => $input_voucher_item->notes
            ]);
        }
        $output_voucher_items = OutputVoucherItem::get();
        foreach ($output_voucher_items as $output_voucher_item) {
            VoucherItemHistory::create([
                'input_voucher_item_id' => $output_voucher_item->input_voucher_item_id,
                'item_id' => $output_voucher_item->item_id,
                'employee_id' => $output_voucher_item->employee_id,
                'voucher_item_historiable_id' => $output_voucher_item->id,
                'voucher_item_historiable_type' => 'App\Models\OutputVoucherItem',
                'price' => $output_voucher_item->price ,
                'count' => $output_voucher_item->count * -1,
                'notes' => $output_voucher_item->notes
            ]);
        }
    }
}
