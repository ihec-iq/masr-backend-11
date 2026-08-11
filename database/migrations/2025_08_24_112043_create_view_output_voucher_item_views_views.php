<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('DROP VIEW IF EXISTS output_voucher_item_views');

        DB::statement('
        CREATE  view output_voucher_item_views as
        SELECT
        output_vouchers.id as `OutputId`,
        items.id as `itemId`,items.name as `itemName`,
        output_voucher_items.count as `count`,
        output_vouchers.number as `numberOutput`,
        output_vouchers.date as `dateOutput`,
        output_voucher_items.price as `price`,
        employees.name as `employeeName`, employees.id  as `employeeId`,
        sections.name as `sectionName`,sections.id as `sectionId`

        from employees,sections , output_vouchers , output_voucher_items , items
        where employees.section_id= sections.id and
        output_vouchers.employee_id = employees.id and
        output_vouchers.id =output_voucher_items.output_voucher_id and
        items.id = output_voucher_items.item_id
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS output_voucher_item_views');
    }
};
