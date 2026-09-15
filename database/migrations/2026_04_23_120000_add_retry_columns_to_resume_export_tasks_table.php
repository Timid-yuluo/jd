<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resume_export_tasks', function (Blueprint $table): void {
            $table->unsignedTinyInteger('attempt_count')->default(0)->after('status');
            $table->unsignedTinyInteger('max_attempts')->default(2)->after('attempt_count');
            $table->timestamp('last_error_at')->nullable()->after('error_message');
        });
    }

    public function down(): void
    {
        Schema::table('resume_export_tasks', function (Blueprint $table): void {
            $table->dropColumn(['attempt_count', 'max_attempts', 'last_error_at']);
        });
    }
};
