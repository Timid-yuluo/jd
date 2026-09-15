<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_sources', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 80);
            $table->string('slug', 80)->unique();
            $table->string('driver', 30)->default('api');
            $table->string('base_url', 255)->nullable();
            $table->json('credentials')->nullable();
            $table->json('sync_config')->nullable();
            $table->unsignedInteger('sync_interval_minutes')->default(360);
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_sync_status', 20)->default('pending');
            $table->unsignedInteger('last_sync_count')->default(0);
            $table->text('last_sync_error')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->timestamps();

            $table->index('is_active');
            $table->index('driver');
        });

        Schema::table('resume_templates', function (Blueprint $table): void {
            $table->unsignedBigInteger('source_id')->nullable()->after('sort_order')->index();
            $table->string('source_driver', 30)->nullable()->after('source_id');
            $table->string('external_id', 120)->nullable()->after('source_driver');
            $table->string('external_url', 500)->nullable()->after('external_id');
            $table->timestamp('synced_at')->nullable()->after('external_url');

            $table->foreign('source_id')->references('id')->on('template_sources')->nullOnDelete();
            $table->unique(['source_id', 'external_id'], 'tpl_source_external_unique');
        });
    }

    public function down(): void
    {
        Schema::table('resume_templates', function (Blueprint $table): void {
            $table->dropForeign(['source_id']);
            $table->dropUnique('tpl_source_external_unique');
            $table->dropColumn(['source_id', 'source_driver', 'external_id', 'external_url', 'synced_at']);
        });

        Schema::dropIfExists('template_sources');
    }
};
