<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_bans', function (Blueprint $table) {
            $table->id();
            $table->string('ban_type')->index()->comment('ip, device, browser, ip_range');
            $table->string('ban_value')->index()->comment('IP地址、设备指纹哈希、浏览器标识');
            $table->string('reason')->default('');
            $table->string('severity')->default('block')->comment('block: 阻止访问, captcha: 要求验证码, log: 仅记录');
            $table->json('metadata')->nullable()->comment('附加信息如地理位置、ISP等');
            $table->unsignedBigInteger('banned_by')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('banned_by');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_bans');
    }
};
