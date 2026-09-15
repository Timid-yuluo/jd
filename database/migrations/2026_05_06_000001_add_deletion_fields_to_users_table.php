<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('deletion_requested_at')->nullable()->after('updated_at')
                ->comment('申请注销时间');
            $table->timestamp('deletion_scheduled_at')->nullable()->after('deletion_requested_at')
                ->comment('计划删除时间（冷静期后）');
            $table->string('deletion_reason')->nullable()->after('deletion_scheduled_at')
                ->comment('注销原因');
            $table->text('deletion_feedback')->nullable()->after('deletion_reason')
                ->comment('注销反馈/建议');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['deletion_requested_at', 'deletion_scheduled_at', 'deletion_reason', 'deletion_feedback']);
        });
    }
};
