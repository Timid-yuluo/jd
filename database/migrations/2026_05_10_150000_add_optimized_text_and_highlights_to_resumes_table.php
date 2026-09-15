<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('resumes', function (Blueprint $table): void {
            if (! Schema::hasColumn('resumes', 'optimized_text')) {
                $table->text('optimized_text')->nullable()->after('ats_score');
            }
            if (! Schema::hasColumn('resumes', 'highlights')) {
                $table->json('highlights')->nullable()->after('optimized_text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('resumes', function (Blueprint $table): void {
            $table->dropColumn(['optimized_text', 'highlights']);
        });
    }
};
