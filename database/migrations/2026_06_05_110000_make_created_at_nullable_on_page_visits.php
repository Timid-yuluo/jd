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
            $table->timestamp('created_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('page_visits', function (Blueprint $table): void {
            $table->timestamp('created_at')->nullable(false)->change();
        });
    }
};
