<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_oauth_accounts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 32);
            $table->string('provider_user_id', 128);
            $table->string('provider_email', 255)->nullable();
            $table->string('provider_name', 255)->nullable();
            $table->timestamp('bound_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'provider']);
            $table->unique(['provider', 'provider_user_id']);
            $table->index(['provider', 'bound_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_oauth_accounts');
    }
};
