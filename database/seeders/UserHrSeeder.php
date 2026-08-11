<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\UserHr;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserHrSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = Employee::get();
        foreach ($employees as $key => $employee) {
            UserHr::create(
                [
                    'employee_id' => $employee->id,
                    'title' => 'bonus ' . $key,
                    'issue_date' => now(),
                    'degree_stage_id' => 67,
                    'study_id' => 1,
                    'certificate_id' => 1,
                    'job_title_id' => 1,
                    'number_last_bonus' => 1,
                    'date_last_bonus' => now(),
                    'date_next_bonus' => now()->addYears(1),
                    'number_last_promotion' => 1,
                    'date_last_promotion' => now(),
                    'date_next_promotion' => now()->addYears(4),
                ]
            );
        }
    }
}
