<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('interview_questions', function (Blueprint $table) {
            $table->json('tags')->nullable()->after('feedback');
            $table->string('difficulty_level', 20)->nullable()->after('tags');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('interview_questions', function (Blueprint $table) {
            $table->dropColumn(['tags', 'difficulty_level']);
        });
    }
};
