<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        //region System Permissions
        $permissions = [
            // Administrator
            ['name' => 'Administrator', 'name_ar' => 'المشرف'],

            // User Management
            ['name' => 'dashboard', 'name_ar' => 'لوحة التحكم'],
            ['name' => 'add user', 'name_ar' => 'إضافة مستخدم'],
            ['name' => 'edit user', 'name_ar' => 'تعديل مستخدم'],
            ['name' => 'delete user', 'name_ar' => 'حذف مستخدم'],
            ['name' => 'show users', 'name_ar' => 'عرض المستخدمين'],

            // Archive Management
            ['name' => 'add archive', 'name_ar' => 'إضافة أرشيف'],
            ['name' => 'edit archive', 'name_ar' => 'تعديل أرشيف'],
            ['name' => 'delete archive', 'name_ar' => 'حذف أرشيف'],
            ['name' => 'show archives', 'name_ar' => 'عرض الأرشيف'],

            // Document Management
            ['name' => 'add document', 'name_ar' => 'إضافة مستند'],
            ['name' => 'delete document', 'name_ar' => 'حذف مستند'],
            ['name' => 'show documents', 'name_ar' => 'عرض المستندات'],

            // Section Management
            ['name' => 'add section', 'name_ar' => 'إضافة قسم'],
            ['name' => 'add user sections', 'name_ar' => 'إضافة أقسام المستخدم'],
            ['name' => 'edit section', 'name_ar' => 'تعديل قسم'],
            ['name' => 'delete section', 'name_ar' => 'حذف قسم'],
            ['name' => 'show sections', 'name_ar' => 'عرض الأقسام'],

            // Archive Type Management
            ['name' => 'add archiveType', 'name_ar' => 'إضافة نوع أرشيف'],
            ['name' => 'edit archiveType', 'name_ar' => 'تعديل نوع أرشيف'],
            ['name' => 'delete archiveType', 'name_ar' => 'حذف نوع أرشيف'],
            ['name' => 'show archiveTypes', 'name_ar' => 'عرض أنواع الأرشيف'],

            // Voucher Management
            ['name' => 'add inputVoucher', 'name_ar' => 'إضافة سند إدخال'],
            ['name' => 'edit inputVoucher', 'name_ar' => 'تعديل سند إدخال'],
            ['name' => 'delete inputVoucher', 'name_ar' => 'حذف سند إدخال'],
            ['name' => 'show inputVouchers', 'name_ar' => 'عرض سندات الإدخال'],
            ['name' => 'show storage', 'name_ar' => 'عرض المخزن'],
            ['name' => 'add outputVoucher', 'name_ar' => 'إضافة سند إخراج'],
            ['name' => 'edit outputVoucher', 'name_ar' => 'تعديل سند إخراج'],
            ['name' => 'delete outputVoucher', 'name_ar' => 'حذف سند إخراج'],
            ['name' => 'show outputVouchers', 'name_ar' => 'عرض سندات الإخراج'],
            ['name' => 'add directVoucher', 'name_ar' => 'إضافة سند مباشر'],
            ['name' => 'edit directVoucher', 'name_ar' => 'تعديل سند مباشر'],
            ['name' => 'delete directVoucher', 'name_ar' => 'حذف سند مباشر'],
            ['name' => 'show directVouchers', 'name_ar' => 'عرض السندات المباشرة'],
            ['name' => 'add retrievalVoucher', 'name_ar' => 'إضافة سند استرجاع'],
            ['name' => 'edit retrievalVoucher', 'name_ar' => 'تعديل سند استرجاع'],
            ['name' => 'delete retrievalVoucher', 'name_ar' => 'حذف سند استرجاع'],
            ['name' => 'show retrievalVouchers', 'name_ar' => 'عرض سندات الاسترجاع'],

            // Item Management
            ['name' => 'add item', 'name_ar' => 'إضافة صنف'],
            ['name' => 'edit item', 'name_ar' => 'تعديل صنف'],
            ['name' => 'delete item', 'name_ar' => 'حذف صنف'],
            ['name' => 'show items', 'name_ar' => 'عرض الأصناف'],

            // Category Management
            ['name' => 'add category item', 'name_ar' => 'إضافة فئة صنف'],
            ['name' => 'edit category item', 'name_ar' => 'تعديل فئة صنف'],
            ['name' => 'delete category item', 'name_ar' => 'حذف فئة صنف'],
            ['name' => 'show categories item', 'name_ar' => 'عرض فئات الأصناف'],

            // Vacation Management
            ['name' => 'vacation office', 'name_ar' => 'إجازة مكتب'],
            ['name' => 'vacation center', 'name_ar' => 'إجازة مركز'],
            ['name' => 'vacation Report', 'name_ar' => 'تقرير الإجازات'],
            ['name' => 'add vacation time', 'name_ar' => 'إضافة وقت إجازة'],
            ['name' => 'edit vacation time', 'name_ar' => 'تعديل وقت إجازة'],
            ['name' => 'delete vacation time', 'name_ar' => 'حذف وقت إجازة'],
            ['name' => 'show vacations time', 'name_ar' => 'عرض أوقات الإجازات'],
            ['name' => 'add vacation daily', 'name_ar' => 'إضافة إجازة يومية'],
            ['name' => 'edit vacation daily', 'name_ar' => 'تعديل إجازة يومية'],
            ['name' => 'delete vacation daily', 'name_ar' => 'حذف إجازة يومية'],
            ['name' => 'show vacations daily', 'name_ar' => 'عرض الإجازات اليومية'],
            ['name' => 'add vacation sick', 'name_ar' => 'إضافة إجازة مرضية'],
            ['name' => 'edit vacation sick', 'name_ar' => 'تعديل إجازة مرضية'],
            ['name' => 'delete vacation sick', 'name_ar' => 'حذف إجازة مرضية'],
            ['name' => 'show vacations sick', 'name_ar' => 'عرض الإجازات المرضية'],

            // Employee Management
            ['name' => 'add employee', 'name_ar' => 'إضافة موظف'],
            ['name' => 'edit employee', 'name_ar' => 'تعديل موظف'],
            ['name' => 'delete employee', 'name_ar' => 'حذف موظف'],
            ['name' => 'show employees', 'name_ar' => 'عرض الموظفين'],

            // Bonus Management
            ['name' => 'add bonus', 'name_ar' => 'إضافة مكافأة'],
            ['name' => 'edit bonus', 'name_ar' => 'تعديل مكافأة'],
            ['name' => 'delete bonus', 'name_ar' => 'حذف مكافأة'],
            ['name' => 'show bonuses', 'name_ar' => 'عرض المكافآت'],

            // Promotion Management
            ['name' => 'add promotion', 'name_ar' => 'إضافة ترقية'],
            ['name' => 'edit promotion', 'name_ar' => 'تعديل ترقية'],
            ['name' => 'delete promotion', 'name_ar' => 'حذف ترقية'],
            ['name' => 'show promotions', 'name_ar' => 'عرض الترقيات'],

            // HR Management
            ['name' => 'add user hr', 'name_ar' => 'إضافة مستخدم موارد بشرية'],
            ['name' => 'edit user hr', 'name_ar' => 'تعديل مستخدم موارد بشرية'],
            ['name' => 'delete user hr', 'name_ar' => 'حذف مستخدم موارد بشرية'],
            ['name' => 'show user hrs', 'name_ar' => 'عرض مستخدمي الموارد البشرية'],

            // Special Permission
            ['name' => 'has section only', 'name_ar' => 'لديه قسم فقط'],

            // Settings Management
            ['name' => 'add warehouse setting', 'name_ar' => 'إضافة إعداد المستودع'],
            ['name' => 'edit warehouse setting', 'name_ar' => 'تعديل إعداد المستودع'],
            ['name' => 'delete warehouse setting', 'name_ar' => 'حذف إعداد المستودع'],
            ['name' => 'show warehouse settings', 'name_ar' => 'عرض إعدادات المستودع'],
            ['name' => 'add employee setting', 'name_ar' => 'إضافة إعداد الموظف'],
            ['name' => 'edit employee setting', 'name_ar' => 'تعديل إعداد الموظف'],
            ['name' => 'delete employee setting', 'name_ar' => 'حذف إعداد الموظف'],
            ['name' => 'show employee setting', 'name_ar' => 'عرض إعداد الموظف'],
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

    }
}
