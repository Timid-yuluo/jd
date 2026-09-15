<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_questions', function (Blueprint $table): void {
            $table->string('dimension', 64)->nullable()->after('round_no');
            $table->index(['interview_session_id', 'dimension']);
        });
    }

    public function down(): void
    {
        Schema::table('interview_questions', function (Blueprint $table): void {
            $table->dropIndex(['interview_session_id', 'dimension']);
            $table->dropColumn('dimension');
        });
    }
};
