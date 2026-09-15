<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table): void {
            $table->string('mode', 20)->default('text')->after('type')->comment('面试模式：text文字，voice语音');
            $table->string('qr_token', 64)->nullable()->after('mode')->comment('扫码面试token');
            $table->timestamp('qr_expires_at')->nullable()->after('qr_token')->comment('二维码过期时间');
        });
    }

    public function down(): void
    {
        Schema::table('interview_sessions', function (Blueprint $table): void {
            $table->dropColumn(['mode', 'qr_token', 'qr_expires_at']);
        });
    }
};
