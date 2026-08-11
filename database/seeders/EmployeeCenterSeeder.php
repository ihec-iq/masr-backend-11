<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeCenter;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmployeeCenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EmployeeCenter::create(['name' => 'لا يوجد','code'=> '']);
        EmployeeCenter::create(['name' => 'الغدير/1','code'=> '1681']);
        EmployeeCenter::create(['name' => 'الغدير/2','code'=> '2681']);
        EmployeeCenter::create(['name' => 'الغدير/3','code'=> '3681']);
        EmployeeCenter::create(['name' => 'سعد / 1','code'=> '1682']);
        EmployeeCenter::create(['name' => 'سعد / 2','code'=> '2682']);
        EmployeeCenter::create(['name' => 'سعد / 3','code'=> '3682']);
        EmployeeCenter::create(['name' => 'الحسينية / 1','code'=> '1683']);
        EmployeeCenter::create(['name' => 'الحسينية / 2','code'=> '2683']);
        EmployeeCenter::create(['name' => 'الحسينية / 3','code'=> '3683']);
        EmployeeCenter::create(['name' => 'الحسينية / 4','code'=> '4683']);
        EmployeeCenter::create(['name' => 'العباسية / 1','code'=> '1684']);
        EmployeeCenter::create(['name' => 'العباسية / 2','code'=> '2684']);
        EmployeeCenter::create(['name' => 'العباسية / 3','code'=> '3684']);
        EmployeeCenter::create(['name' => 'عين التمر','code'=> '1685']);
        EmployeeCenter::create(['name' => 'الهندية / 1','code'=> '1686']);
        EmployeeCenter::create(['name' => 'الهندية /2','code'=> '2686']);
        EmployeeCenter::create(['name' => 'الهندية / 3','code'=> '3686']);
        EmployeeCenter::create(['name' => 'الجدول الغربي / 1','code'=> '1687']);
        EmployeeCenter::create(['name' => 'الجدول الغربي / 2','code'=> '2687']);
        EmployeeCenter::create(['name' => 'الجدول الغربي / 3','code'=> '3687']);
        EmployeeCenter::create(['name' => 'الجدول الغربي / 4','code'=> '4687']);
        EmployeeCenter::create(['name' => 'الحر / 1','code'=> '1688']);
        EmployeeCenter::create(['name' => 'الحر / 2','code'=> '2688']);
        EmployeeCenter::create(['name' => 'الحر / 3','code'=> '3688']);
        EmployeeCenter::create(['name' => 'الحر / 4','code'=> '4688']);
        EmployeeCenter::create(['name' => 'العامل / 1','code'=> '1689']);
        EmployeeCenter::create(['name' => 'العامل / 2','code'=> '2689']);
        EmployeeCenter::create(['name' => 'العامل / 3','code'=> '3689']);
        EmployeeCenter::create(['name' => 'الاسرة / 1','code'=> '1690']);
        EmployeeCenter::create(['name' => 'الاسرة / 2','code'=> '1690']);
        EmployeeCenter::create(['name' => 'رمضان / 1','code'=> '1691']);
        EmployeeCenter::create(['name' => 'رمضان / 2','code'=> '2691']);
        EmployeeCenter::create(['name' => 'باب بغداد / 1','code'=> '1692']);
        EmployeeCenter::create(['name' => 'باب بغداد / 2','code'=> '2692']);
        // $employees = Employee::get();
        // $employees->update(['employee_center_id' => 1]);
    }
}
