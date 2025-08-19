<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->constrained('users')->onDelete('cascade');
            $table->string('action');
            $table->string('target_type');
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};
