<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('login_histories', function (Blueprint $table): void {
            $table->string('event_type', 20)->nullable()->after('is_success')->comment('login, logout, failed');
        });
    }

    public function down(): void
    {
        Schema::table('login_histories', function (Blueprint $table): void {
            $table->dropColumn('event_type');
        });
    }
};
