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
        Schema::table('payrolls', function (Blueprint $table) {
            $table->string('tax_code')->nullable()->after('gross_pay');
            $table->decimal('national_insurance', 15, 2)->default(0)->after('paye_tax');
            $table->decimal('student_loan_deduction', 15, 2)->default(0)->after('national_insurance');
            $table->decimal('pension_contribution', 15, 2)->default(0)->after('student_loan_deduction');
            $table->decimal('employer_pension_contribution', 15, 2)->default(0)->after('pension_contribution');
            $table->string('nic_category')->nullable()->after('employer_pension_contribution');
            $table->string('student_loan_plan')->nullable()->after('nic_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'tax_code',
                'national_insurance',
                'student_loan_deduction',
                'pension_contribution',
                'employer_pension_contribution',
                'nic_category',
                'student_loan_plan'
            ]);
        });
    }
};
