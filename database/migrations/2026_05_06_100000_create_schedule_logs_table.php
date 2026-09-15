<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_logs', function (Blueprint $table) {
            $table->id();
            $table->string('task_name', 100)->index()->comment('任务名称');
            $table->string('task_description', 255)->nullable()->comment('任务描述');
            $table->enum('status', ['running', 'success', 'failed'])->default('running')->comment('执行状态');
            $table->longText('output')->nullable()->comment('执行输出');
            $table->unsignedInteger('duration_ms')->nullable()->comment('执行耗时（毫秒）');
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete()->comment('触发用户（手动执行时）');
            $table->timestamps();

            $table->index(['task_name', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_logs');
    }
};
