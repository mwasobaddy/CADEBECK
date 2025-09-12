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
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('payroll_period'); // e.g., '2025-09'
            $table->date('pay_date');
            $table->decimal('basic_salary', 15, 2);
            $table->decimal('house_allowance', 15, 2)->default(0);
            $table->decimal('transport_allowance', 15, 2)->default(0);
            $table->decimal('medical_allowance', 15, 2)->default(0);
            $table->decimal('other_allowances', 15, 2)->default(0);
            $table->decimal('total_allowances', 15, 2)->default(0);

            // Overtime and bonuses
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('overtime_rate', 15, 2)->default(0);
            $table->decimal('overtime_amount', 15, 2)->default(0);
            $table->decimal('bonus_amount', 15, 2)->default(0);

            // Gross Pay
            $table->decimal('gross_pay', 15, 2);

            // Deductions
            $table->decimal('paye_tax', 15, 2)->default(0);
            $table->decimal('nhif_deduction', 15, 2)->default(0);
            $table->decimal('nssf_deduction', 15, 2)->default(0);
            $table->decimal('insurance_deduction', 15, 2)->default(0);
            $table->decimal('loan_deduction', 15, 2)->default(0);
            $table->decimal('other_deductions', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);

            // Net Pay
            $table->decimal('net_pay', 15, 2);

            // Tax Information
            $table->decimal('taxable_income', 15, 2)->default(0);
            $table->decimal('personal_relief', 15, 2)->default(2400); // USD 2,400
            $table->decimal('insurance_relief', 15, 2)->default(0);
            $table->decimal('total_relief', 15, 2)->default(0);

            // Status and metadata
            $table->enum('status', ['draft', 'processed', 'paid'])->default('draft');
            $table->text('notes')->nullable();
            $table->json('calculation_details')->nullable(); // Store detailed calculation breakdown
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();

            // Indexes
            $table->index(['employee_id', 'payroll_period']);
            $table->index('pay_date');
            $table->index('status');
            $table->unique(['employee_id', 'payroll_period']); // One payroll per employee per period
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
