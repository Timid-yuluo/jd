<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quota_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('quota_key', 64);
            $table->string('period', 8);
            $table->unsignedInteger('used')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'quota_key', 'period'], 'uk_user_quota_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quota_usages');
    }
};
