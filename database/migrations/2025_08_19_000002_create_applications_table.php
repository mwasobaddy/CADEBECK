<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_advert_id')->constrained('job_adverts')->onDelete('cascade');
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->binary('cv_blob');
            $table->string('cover_letter', 600);
            $table->enum('status', ['Pending', 'Shortlisted', 'Rejected', 'Invited'])->default('Pending');
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
