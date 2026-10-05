<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            // Nullable on purpose: the audit trail also covers platform level
            // activity, such as a Developer adding a client or creating platform
            // staff, which has no client of its own.
            $table->foreignId('client_id')->nullable()->index()->constrained('clients');
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
