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
            if (! Schema::hasColumn('users', 'is_suspended')) {
                $table->boolean('is_suspended')->default(false)->after('current_plan_slug')
                    ->comment('是否被后台封禁');
            }
            if (! Schema::hasColumn('users', 'suspended_at')) {
                $table->timestamp('suspended_at')->nullable()->after('is_suspended')
                    ->comment('封禁时间');
            }
            if (! Schema::hasColumn('users', 'suspended_reason')) {
                $table->string('suspended_reason', 255)->nullable()->after('suspended_at')
                    ->comment('封禁原因');
            }
            if (! Schema::hasColumn('users', 'suspended_by')) {
                $table->unsignedBigInteger('suspended_by')->nullable()->after('suspended_reason')
                    ->comment('封禁操作者');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $dropColumns = [];
            foreach (['is_suspended', 'suspended_at', 'suspended_reason', 'suspended_by'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
