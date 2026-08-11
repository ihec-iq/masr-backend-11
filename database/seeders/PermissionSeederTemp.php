<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeederTemp extends Seeder
{
    public function run(): void
    {
        //region System Permissions
        $permissions = [
             // Backup Management
            ['name' => 'show backups', 'name_ar' => 'عرض النسخ الاحتياطية'],
            ['name' => 'create backup', 'name_ar' => 'إنشاء نسخة احتياطية'],
            ['name' => 'delete backup', 'name_ar' => 'حذف نسخة احتياطية'],
            ['name' => 'restore backup', 'name_ar' => 'استعادة نسخة احتياطية'],
            ['name' => 'download backup', 'name_ar' => 'تحميل نسخة احتياطية'],
            ['name' => 'manage backup settings', 'name_ar' => 'إدارة إعدادات النسخ الاحتياطية'],
            ['name' => 'manage backup admins', 'name_ar' => 'إدارة مسؤولي النسخ الاحتياطية'],
            ['name' => 'show backup logs', 'name_ar' => 'عرض سجلات النسخ الاحتياطية'],
            ['name' => 'show backup health', 'name_ar' => 'عرض حالة النسخ الاحتياطية'],
        ];

        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        $permissions = Permission::all();
        $adminRole = Role::where('name', 'Administrator')->first();
        if ($adminRole) {
            $adminRole->syncPermissions([]); // Remove all old permissions
            $adminRole->syncPermissions($permissions); // Add all permissions
        }
        $users = \App\Models\User::role('Administrator')->get();
        foreach ($users as $user) {
            $user->syncRoles([]); // Remove all old roles
            $user->assignRole('Administrator'); // Add "Administrator" role again
        }

    }
}
