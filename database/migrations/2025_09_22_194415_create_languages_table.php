<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique(); // Language code (eng, fr, etc.)
            $table->string('name'); // Language name (English, French, etc.)
            $table->string('native_name'); // Native language name (English, Français, etc.)
            $table->boolean('is_active')->default(true); // Whether language is available
            $table->integer('sort_order')->default(0); // For sorting languages
            $table->timestamps();
        });

        // Insert default languages
        DB::table('languages')->insert([
            [
                'code' => 'en',
                'name' => 'English',
                'native_name' => 'English',
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'fr',
                'name' => 'French',
                'native_name' => 'Français',
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
