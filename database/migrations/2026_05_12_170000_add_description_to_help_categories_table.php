<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('help_categories', function (Blueprint $table): void {
            $table->string('description', 200)->nullable()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('help_categories', function (Blueprint $table): void {
            $table->dropColumn('description');
        });
    }
};
