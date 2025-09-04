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
            $table->date('response_date');
            $table->integer('stress_level')->comment('1-10 scale');
            $table->integer('work_life_balance')->comment('1-10 scale');
            $table->integer('job_satisfaction')->comment('1-10 scale');
            $table->integer('support_level')->comment('1-10 scale');
            $table->text('comments')->nullable();
            $table->json('additional_metrics')->nullable(); // For future expansion
            $table->timestamps();

            $table->unique(['employee_id', 'response_date']); // One response per employee per day
            $table->index(['response_date', 'stress_level']);
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
