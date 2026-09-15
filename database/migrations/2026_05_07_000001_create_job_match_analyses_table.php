<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_match_analyses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('resume_id')->nullable()->constrained()->nullOnDelete();
            $table->text('job_description');
            $table->json('result');
            $table->unsignedTinyInteger('match_score')->default(0);
            $table->string('level', 30)->nullable();
            $table->string('summary', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'match_score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_match_analyses');
    }
};
