<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resume_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resume_id')->constrained()->cascadeOnDelete();
            $table->string('type', 30); // personal, education, experience, project, skill, certificate, summary
            $table->json('data');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['resume_id', 'type']);
            $table->index(['resume_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_modules');
    }
};
