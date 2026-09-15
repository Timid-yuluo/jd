<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resume_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('content_raw');
            $table->json('content_structured')->nullable();
            $table->string('title');
            $table->string('target_job')->nullable();
            $table->json('modules_snapshot')->nullable();
            $table->text('change_summary')->nullable();
            $table->timestamps();

            $table->index(['resume_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_versions');
    }
};
