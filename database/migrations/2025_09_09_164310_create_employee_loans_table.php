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
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('loan_type'); // personal, housing, vehicle, etc.
            $table->string('loan_number')->unique();
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('interest_rate', 5, 2); // Annual interest rate
            $table->integer('term_months'); // Loan term in months
            $table->decimal('monthly_installment', 15, 2);
            $table->decimal('total_amount', 15, 2); // Principal + Interest
            $table->decimal('remaining_balance', 15, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->date('disbursement_date')->nullable();
            $table->enum('status', ['pending', 'active', 'completed', 'defaulted'])->default('pending');
            $table->text('purpose')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->json('repayment_schedule')->nullable(); // Store monthly repayment details
            $table->timestamps();

            // Indexes
            $table->index(['employee_id', 'status']);
            $table->index('loan_number');
            $table->index('start_date');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_loans');
    }
};
