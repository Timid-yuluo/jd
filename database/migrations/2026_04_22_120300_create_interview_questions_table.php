<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interview_questions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('interview_session_id')->constrained('interview_sessions')->cascadeOnDelete();
            $table->unsignedSmallInteger('round_no')->default(1);
            $table->text('question');
            $table->text('answer')->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            $table->json('feedback')->nullable();
            $table->timestamps();

            $table->index(['interview_session_id', 'round_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interview_questions');
    }
};
