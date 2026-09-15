<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('verification_token')->nullable()->after('email_verified_at')->comment('邮箱验证令牌');
            $table->timestamp('verification_token_expires_at')->nullable()->after('verification_token')->comment('验证令牌过期时间');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['verification_token', 'verification_token_expires_at']);
        });
    }
};
