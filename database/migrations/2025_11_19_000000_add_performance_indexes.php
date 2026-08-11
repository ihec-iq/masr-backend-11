<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // // Employees table indexes
        // // Schema::table('employees', function (Blueprint $table) {
        // //     $table->index('name'); // For name searches
        // //     $table->index('number'); // For employee number lookups
        // //     $table->index('section_id'); // For section filtering
        // //     $table->index('employee_type_id'); // For type filtering
        // //     $table->index('employee_center_id'); // For center filtering
        // //     $table->index('is_person'); // For person filtering
        // //     $table->index('date_next_bonus'); // For bonus queries
        // //     $table->index(['section_id', 'is_person']); // Composite for common query
        // // });

        // // Vacations table indexes
        // Schema::table('vacations', function (Blueprint $table) {
        //     // $table->index('employee_id'); // For employee lookups
        //     //$table->index('record'); // For record filtering
        //     //$table->index('record_sick'); // For sick record filtering
        // });

        // // Archives table indexes
        // Schema::table('archives', function (Blueprint $table) {
        //     $table->index('number'); // For number searches
        //     $table->index('title'); // For title searches
        //     $table->index('issue_date'); // For date filtering
        //     $table->index('is_in'); // For direction filtering
        //     $table->index(['archive_type_id', 'issue_date']); // Composite for common query
        // });

        // // HR Documents table indexes
        // Schema::table('hr_documents', function (Blueprint $table) {
        //     $table->index('employee_id'); // For employee filtering
        //     $table->index('hr_document_type_id'); // For type filtering
        //     $table->index('issue_date'); // For date filtering
        //     $table->index('number'); // For number searches
        //     $table->index(['employee_id', 'issue_date']); // Composite for common query
        // });

        // // Input Vouchers table indexes
        // Schema::table('input_vouchers', function (Blueprint $table) {
        //     $table->index('employee_id'); // For employee filtering
        //     $table->index('stock_id'); // For stock filtering
        //     $table->index('number'); // For number searches
        //     $table->index('date'); // For date filtering
        //     $table->index('input_voucher_state_id'); // For state filtering
        // });

        // // Output Vouchers table indexes
        // Schema::table('output_vouchers', function (Blueprint $table) {
        //     $table->index('employee_id'); // For employee filtering
        //     $table->index('stock_id'); // For stock filtering
        //     $table->index('number'); // For number searches
        //     $table->index('date'); // For date filtering
        // });

        // // Items table indexes
        // Schema::table('items', function (Blueprint $table) {
        //     $table->index('item_category_id'); // For category filtering
        //     $table->index('name'); // For name searches
        // });

        // // Users table indexes
        // Schema::table('users', function (Blueprint $table) {
        //     $table->index('active'); // For active user filtering
        //     $table->index('email'); // Already unique, but explicit index
        // });

        // // Sections table indexes
        // Schema::table('sections', function (Blueprint $table) {
        //     $table->index('name'); // For name searches
        //     $table->index('department_id'); // For department filtering
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop employees indexes
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['employee_number']);
            $table->dropIndex(['section_id']);
            $table->dropIndex(['employee_type_id']);
            $table->dropIndex(['employee_center_id']);
            $table->dropIndex(['is_person']);
            $table->dropIndex(['date_next_bonus']);
            $table->dropIndex(['section_id', 'is_person']);
        });

        // Drop vacations indexes
        Schema::table('vacations', function (Blueprint $table) {
            $table->dropIndex(['employee_id']);
            $table->dropIndex(['record']);
            $table->dropIndex(['record_sick']);
        });

        // Drop archives indexes
        Schema::table('archives', function (Blueprint $table) {
            $table->dropIndex(['archive_type_id']);
            $table->dropIndex(['number']);
            $table->dropIndex(['title']);
            $table->dropIndex(['issue_date']);
            $table->dropIndex(['is_in']);
            $table->dropIndex(['archive_type_id', 'issue_date']);
        });

        // Drop hr_documents indexes
        Schema::table('hr_documents', function (Blueprint $table) {
            $table->dropIndex(['employee_id']);
            $table->dropIndex(['hr_document_type_id']);
            $table->dropIndex(['issue_date']);
            $table->dropIndex(['number']);
            $table->dropIndex(['employee_id', 'issue_date']);
        });

        // Drop input_vouchers indexes
        Schema::table('input_vouchers', function (Blueprint $table) {
            $table->dropIndex(['employee_id']);
            $table->dropIndex(['stock_id']);
            $table->dropIndex(['number']);
            $table->dropIndex(['date']);
            $table->dropIndex(['input_voucher_state_id']);
        });

        // Drop output_vouchers indexes
        Schema::table('output_vouchers', function (Blueprint $table) {
            $table->dropIndex(['employee_id']);
            $table->dropIndex(['stock_id']);
            $table->dropIndex(['number']);
            $table->dropIndex(['date']);
        });

        // Drop items indexes
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['item_category_id']);
            $table->dropIndex(['name']);
        });

        // Drop users indexes
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['active']);
            $table->dropIndex(['email']);
        });

        // Drop sections indexes
        Schema::table('sections', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['department_id']);
        });
    }
};
