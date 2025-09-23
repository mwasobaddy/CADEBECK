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
        Schema::create('well_being_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Assessment metadata
            $table->enum('assessment_type', ['daily', 'weekly', 'monthly'])->default('daily');
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->string('frequency');

            // Assessment data (nullable for flexibility across assessment types)
            $table->integer('stress_level')->nullable()->comment('1-10 scale');
            $table->integer('work_life_balance')->nullable()->comment('1-10 scale');
            $table->integer('job_satisfaction')->nullable()->comment('1-10 scale');
            $table->integer('support_level')->nullable()->comment('1-10 scale');
            $table->text('comments')->nullable();
            $table->json('additional_metrics')->nullable(); // For future expansion

            $table->timestamps();

            // Prevent duplicate assessments for same period
            $table->unique(['employee_id', 'assessment_type', 'period_start_date'], 'unique_employee_assessment_period');

            // Indexes for performance
            $table->index(['assessment_type', 'period_start_date']);
            $table->index(['employee_id', 'assessment_type']);
            $table->index(['period_start_date', 'period_end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('well_being_responses');
    }
};
