<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resume_optimize_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('session_id')
                ->constrained('resume_optimize_sessions')
                ->cascadeOnDelete();
            $table->longText('before_raw')->nullable();
            $table->longText('after_raw')->nullable();
            $table->json('before_modules')->nullable();
            $table->json('after_modules')->nullable();
            $table->json('diff_map')->nullable();
            $table->json('score_delta')->nullable();
            $table->json('risk_tips')->nullable();
            $table->json('highlights')->nullable();
            $table->timestamps();

            $table->unique('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_optimize_versions');
    }
};
