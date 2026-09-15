<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_visits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id', 64)->index();
            $table->string('path', 500);
            $table->string('route_name', 100)->nullable()->index();
            $table->string('method', 10)->default('GET');
            $table->string('ip_address', 45)->index();
            $table->string('real_ip', 45)->nullable()->index();
            $table->string('user_agent', 500)->nullable();
            $table->string('device_type', 20)->nullable();
            $table->string('browser', 50)->nullable();
            $table->string('os', 50)->nullable();
            $table->string('referer', 500)->nullable();
            $table->string('country', 50)->nullable();
            $table->string('city', 50)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('event_type', 30)->default('pageview');
            $table->string('event_label', 255)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->index();

            $table->index(['user_id', 'created_at']);
            $table->index(['created_at', 'path']);
            $table->index(['created_at', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_visits');
    }
};
