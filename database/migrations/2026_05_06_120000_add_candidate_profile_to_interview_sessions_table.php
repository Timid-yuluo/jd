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
            $table->string('candidate_profile', 32)
                ->default('fresh_graduate')
                ->after('job_description')
                ->comment('候选人身份：fresh_graduate/no_experience/junior/experienced');
        });
    }

    public function down(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table): void {
            $table->dropColumn('candidate_profile');
        });
    }
};
