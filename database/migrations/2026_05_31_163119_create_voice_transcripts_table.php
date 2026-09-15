<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voice_transcripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('interview_session_id')->constrained('interview_sessions')->cascadeOnDelete();
            $table->foreignId('question_id')->nullable()->constrained('interview_questions')->nullOnDelete();
            $table->text('transcript');
            $table->string('language', 10)->default('zh');
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('source', 20)->default('client');
            $table->timestamps();

            $table->index(['interview_session_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voice_transcripts');
    }
};
