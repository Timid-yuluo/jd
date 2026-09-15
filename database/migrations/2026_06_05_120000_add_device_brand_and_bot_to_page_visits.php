<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_visits', function (Blueprint $table): void {
            $table->string('device_brand', 50)->nullable()->after('device_type');
            $table->string('os_version', 30)->nullable()->after('os');
            $table->string('browser_version', 30)->nullable()->after('browser');
            $table->boolean('is_bot')->default(false)->after('browser_version');
            $table->string('bot_name', 50)->nullable()->after('is_bot');

            $table->index('device_brand');
            $table->index('is_bot');
        });
    }

    public function down(): void
    {
        Schema::table('page_visits', function (Blueprint $table): void {
            $table->dropIndex(['device_brand']);
            $table->dropIndex(['is_bot']);
            $table->dropColumn(['device_brand', 'os_version', 'browser_version', 'is_bot', 'bot_name']);
        });
    }
};
