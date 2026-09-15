<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_recruitments', function (Blueprint $table): void {
            if (! Schema::hasColumn('external_recruitments', 'source_name')) {
                $table->string('source_name', 100)->nullable()->after('source_id');
            }
            if (! Schema::hasColumn('external_recruitments', 'source_url')) {
                $table->string('source_url', 1000)->nullable()->after('source_name');
            }
            if (! Schema::hasColumn('external_recruitments', 'raw_payload')) {
                $table->json('raw_payload')->nullable()->after('remarks');
            }
            if (! Schema::hasColumn('external_recruitments', 'imported_at')) {
                $table->timestamp('imported_at')->nullable()->after('raw_payload');
            }
            if (! Schema::hasColumn('external_recruitments', 'review_status')) {
                $table->string('review_status', 20)->default('pending')->after('imported_at');
            }
            if (! Schema::hasColumn('external_recruitments', 'review_note')) {
                $table->text('review_note')->nullable()->after('review_status');
            }
            if (! Schema::hasColumn('external_recruitments', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('review_note');
            }
            if (! Schema::hasColumn('external_recruitments', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('external_recruitments', function (Blueprint $table): void {
            $table->dropColumn([
                'source_name', 'source_url', 'raw_payload', 'imported_at',
                'review_status', 'review_note', 'reviewed_by', 'reviewed_at',
            ]);
        });
    }
};
