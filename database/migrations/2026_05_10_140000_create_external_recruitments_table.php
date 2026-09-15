<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_recruitments', function (Blueprint $table): void {
            $table->id();
            $table->string('source_id', 64)->unique()->comment('来源站唯一ID');
            $table->string('company', 255)->comment('公司名称');
            $table->string('title', 500)->comment('招聘标题');
            $table->string('work_location', 255)->nullable()->comment('工作地点');
            $table->string('industry', 255)->nullable()->comment('行业');
            $table->text('positions')->nullable()->comment('招聘岗位');
            $table->string('channel', 50)->nullable()->comment('招聘渠道（校招/社招）');
            $table->string('apply_url', 1000)->nullable()->comment('投递链接');
            $table->json('position_tags')->nullable()->comment('岗位标签');
            $table->json('work_locations')->nullable()->comment('工作地点数组');
            $table->timestamp('deadline')->nullable()->comment('截止时间');
            $table->string('fingerprint', 128)->nullable()->comment('去重指纹');
            $table->text('remarks')->nullable()->comment('备注');
            $table->timestamp('source_created_at')->nullable()->comment('来源创建时间');
            $table->timestamp('source_updated_at')->nullable()->comment('来源更新时间');
            $table->timestamps();

            $table->index('company');
            $table->index('industry');
            $table->index('work_location');
            $table->index('channel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_recruitments');
    }
};
