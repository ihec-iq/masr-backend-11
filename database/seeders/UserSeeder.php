<?php

namespace Database\Seeders;

use App\Models\Bonus;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //region Roles

        $permissions = Permission::get();
        $adminRole = Role::create(['name' => 'Administrator']);
        $adminRole->syncPermissions($permissions); // This line can be adjusted based on your needs

        $superAdmin = Role::create(['name' => 'Super-Admin']);
        $superAdmin->syncPermissions($permissions); // This line can be adjusted based on your needs

        $hrRole = Role::create(['name' => 'HR']);
        $hrRole->syncPermissions(['add archive', 'edit archive', 'delete archive', 'show archives']);

        // Special permission
        // Permission::create(['name' => 'hisSectionOnly']); // This line has been removed

        //endregion

        //region Users

        $admin = User::create([
            'name' => 'admin',
            'user_name' => 'admin',
            'password' => Hash::make('password'),
            'email' => 'admin@admin.com',
        ]);

        $admin->sections()->attach(
            2,
            ['is_main' => true]
        );

        $admin->assignRole($adminRole);

        $user = User::create([
            'name' => 'Department',
            'user_name' => 'Department',
            'password' => Hash::make('password'),
            'email' => 'department@admin.com',
            'active' => 0,
        ]);
        $user->sections()->attach(
            1,
            ['is_main' => true]
        );

        // $user1 = User::create([
        //     "name" => "user1",
        //     "password" => Hash::make("user1"),
        //     "email" => "user1@admin.com",
        // ]);
        // $user1->sections()->attach(
        //     4,
        //     ["is_main" => false]
        // );

        // $hr1 = User::create([
        //     "name" => "hr1",
        //     "password" => Hash::make("hr1"),
        //     "email" => "hr1@ihec.com",
        // ]);
        // $hr1->sections()->attach(
        //     3,
        //     ["is_main" => false]
        // );

        // $hr1->assignRole($hrRole);

        //region Users

        //endregion
        // DB::statement('TRUNCATE TABLE users;');
        // DB::statement('TRUNCATE TABLE employees;');



        DB::statement(
            '
                    INSERT INTO `employees` (`name`, `is_person`, `section_id`, `user_id`, `id_card`, `number`, `date_work`, `init_vacation`, `take_vacation`, `init_vacation_sick`, `take_vacation_sick`, `created_at`, `updated_at`, `deleted_at`,`employee_type_id`,`employee_position_id`,move_section_id,employee_center_id) VALUES
                    ( "Admin", 0, 1, 1, NULL, NULL, NULL, 0, 0, 0, 0, "2023-12-26 08:49:04", "2023-12-26 08:49:04", NULL,1,1,1,1),
                    ( "Department", 0, 1, 2, NULL, NULL, NULL, 0, 0, 0, 0, "2023-12-26 08:49:04", "2023-12-26 08:49:04", NULL,1,1,1,1);
        '
        );

        $employees = Employee::get();
        foreach ($employees as $employee) {
            Bonus::create([
                'employee_id' => $employee->id,
                'degree_stage_id' => 65,
                'issue_date' => now() ,
                'number'=>'',
                'notes'=>'الترفيع الاولي , يجب ذكر كتب اخر ترفيع هنا'
            ]);
        }
    }

}
