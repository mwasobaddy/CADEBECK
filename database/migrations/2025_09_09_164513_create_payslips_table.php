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
        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->string('payslip_number')->unique();
            $table->string('payroll_period');
            $table->date('pay_date');
            $table->string('file_path')->nullable(); // Path to PDF file
            $table->string('file_name');
            $table->json('payslip_data'); // Store complete payslip data for regeneration
            $table->boolean('is_emailed')->default(false);
            $table->timestamp('emailed_at')->nullable();
            $table->boolean('is_downloaded')->default(false);
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['employee_id', 'payroll_period']);
            $table->index('payslip_number');
            $table->index('pay_date');
            $table->index('is_emailed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payslips');
    }
};
