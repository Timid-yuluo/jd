<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_action_logs', function (Blueprint $table): void {
            $table->index('created_at', 'admin_action_logs_created_at_index');
            $table->index(['user_id', 'created_at'], 'admin_action_logs_user_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('admin_action_logs', function (Blueprint $table): void {
            $table->dropIndex('admin_action_logs_created_at_index');
            $table->dropIndex('admin_action_logs_user_date_index');
        });
    }
};
