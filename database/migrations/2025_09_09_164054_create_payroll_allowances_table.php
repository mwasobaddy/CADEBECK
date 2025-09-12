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
        Schema::create('payroll_allowances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('payroll_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('allowance_type'); // house, transport, medical, overtime, bonus, etc.
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->boolean('is_recurring')->default(true); // Monthly recurring or one-time
            $table->date('effective_date');
            $table->date('end_date')->nullable(); // For temporary allowances
            $table->enum('status', ['active', 'inactive', 'expired'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['employee_id', 'allowance_type']);
            $table->index(['payroll_id', 'allowance_type']);
            $table->index('status');
            $table->index('effective_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_allowances');
    }
};
