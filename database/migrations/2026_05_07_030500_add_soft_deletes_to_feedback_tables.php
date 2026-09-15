<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('feedbacks') && ! Schema::hasColumn('feedbacks', 'deleted_at')) {
            Schema::table('feedbacks', function (Blueprint $table): void {
                $table->softDeletes()->index();
            });
        }

        if (Schema::hasTable('feedback_replies') && ! Schema::hasColumn('feedback_replies', 'deleted_at')) {
            Schema::table('feedback_replies', function (Blueprint $table): void {
                $table->softDeletes()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('feedback_replies') && Schema::hasColumn('feedback_replies', 'deleted_at')) {
            Schema::table('feedback_replies', function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasTable('feedbacks') && Schema::hasColumn('feedbacks', 'deleted_at')) {
            Schema::table('feedbacks', function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }
};
