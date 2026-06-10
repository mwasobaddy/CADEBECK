<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('salary_frequency', ['monthly', 'annual', 'hourly'])
                ->default('monthly')
                ->after('basic_salary');
            $table->decimal('contracted_hours_per_week', 4, 1)
                ->nullable()
                ->after('salary_frequency');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['salary_frequency', 'contracted_hours_per_week']);
        });
    }
};
