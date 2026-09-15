<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_access_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address')->index();
            $table->string('device_fingerprint')->nullable()->index()->comment('客户端设备指纹哈希');
            $table->string('browser_signature')->nullable()->index()->comment('浏览器特征签名');
            $table->string('action', 50)->default('access')->comment('access, denied, banned, suspicious');
            $table->string('path')->nullable();
            $table->text('details')->nullable()->comment('拒绝原因等详细信息');
            $table->json('request_headers')->nullable()->comment('关键请求头快照');
            $table->timestamp('accessed_at')->nullable()->index();

            $table->index(['ip_address', 'accessed_at']);
            $table->index(['device_fingerprint', 'accessed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_access_logs');
    }
};
