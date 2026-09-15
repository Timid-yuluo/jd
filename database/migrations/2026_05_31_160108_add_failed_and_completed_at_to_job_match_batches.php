<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_match_batches', function (Blueprint $table) {
            $table->unsignedInteger('failed')->default(0)->after('completed');
            $table->timestamp('completed_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('job_match_batches', function (Blueprint $table) {
            $table->dropColumn(['failed', 'completed_at']);
        });
    }
};
