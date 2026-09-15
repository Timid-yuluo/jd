<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_applications', function (Blueprint $table): void {
            $table->unsignedBigInteger('resume_id')->nullable()->change();
        });
        Schema::table('job_applications', function (Blueprint $table): void {
            $table->foreign('resume_id')->references('id')->on('resumes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table): void {
            $table->dropForeign(['resume_id']);
        });
        Schema::table('job_applications', function (Blueprint $table): void {
            $table->integer('resume_id')->nullable()->change();
        });
    }
};
