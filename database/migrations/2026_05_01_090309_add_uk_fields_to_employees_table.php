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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('tax_code')->nullable()->after('basic_salary');
            $table->string('nic_category', 1)->default('A')->after('tax_code');
            $table->string('student_loan_plan')->nullable()->after('nic_category');
            $table->boolean('include_pension')->default(true)->after('student_loan_plan');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['tax_code', 'nic_category', 'student_loan_plan', 'include_pension']);
        });
    }
};
