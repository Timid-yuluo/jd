<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('quota_key', 64)->nullable();
            $table->unsignedInteger('remaining')->default(0);
            $table->string('source_type', 32);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'quota_key'], 'idx_user_credits_user_quota');
            $table->index('expires_at', 'idx_user_credits_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_credits');
    }
};
