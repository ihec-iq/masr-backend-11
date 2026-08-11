<?php

namespace Database\Seeders;

use App\Models\InputVoucher;
use Illuminate\Database\Seeder;

class InputVoucherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //$data = InputVoucher::factory()->count(100)->create();
        InputVoucher::create([
            'input_voucher_state_id' => 1,
            'requested_by' => 'مكتب كربلاء',
            'number' => '001',
            'date' => '2023-08-06',
            'notes' => 'مستند ادخال كراسي مخزن', 
        ]);
        InputVoucher::create([
            'input_voucher_state_id' => 2,
            'requested_by' => 'مكتب كربلاء',
            'number' => '002',
            'date' => '2023-08-07',
            'notes' => 'مستند ادخال مخزن',
        ]);
    }
}
