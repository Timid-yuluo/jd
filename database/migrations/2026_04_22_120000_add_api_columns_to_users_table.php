<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('wechat_openid', 100)->nullable()->unique()->after('email');
            $table->string('api_token', 64)->nullable()->unique()->after('remember_token');
            $table->string('school', 100)->nullable()->after('api_token');
            $table->string('major', 100)->nullable()->after('school');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['wechat_openid', 'api_token', 'school', 'major']);
        });
    }
};
