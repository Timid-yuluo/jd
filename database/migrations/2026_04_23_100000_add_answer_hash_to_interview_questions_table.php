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
            $table->string('answer_hash', 64)->nullable()->after('answer');
            $table->index(['interview_session_id', 'answer_hash']);
        });
    }

    public function down(): void
    {
        Schema::table('interview_questions', function (Blueprint $table): void {
            $table->dropIndex(['interview_session_id', 'answer_hash']);
            $table->dropColumn('answer_hash');
        });
    }
};
