<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resume_id')->nullable()->constrained()->nullOnDelete();
            $table->string('position', 120);
            $table->string('company', 120)->nullable();
            $table->string('type', 30)->default('hr');
            $table->string('status', 30)->default('in_progress');
            $table->unsignedSmallInteger('question_count')->default(0);
            $table->unsignedSmallInteger('answered_count')->default(0);
            $table->unsignedTinyInteger('overall_score')->nullable();
            $table->json('report')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_sessions');
    }
};
