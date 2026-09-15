<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table): void {
            $table->text('job_description')->nullable()->after('company');
        });
    }

    public function down(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table): void {
            $table->dropColumn('job_description');
        });
    }
};
