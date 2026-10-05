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
        Schema::create('launch_subscribers', function (Blueprint $table) {
            $table->id();
            // Nullable on purpose: pre-launch signups are captured from anonymous
            // visitors on the public under-construction page, so they belong to no
            // client yet. Deliberately not treated as tenant data.
            $table->foreignId('client_id')->nullable()->index()->constrained('clients');
            $table->string('email')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('launch_subscribers');
    }
};