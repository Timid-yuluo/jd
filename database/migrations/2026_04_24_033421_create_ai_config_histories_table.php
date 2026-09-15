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
        Schema::create('ai_config_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->comment('操作人ID');
            $table->string('user_name', 100)->nullable()->comment('操作人姓名');
            $table->json('changes')->comment('变更内容');
            $table->json('verify_results')->nullable()->comment('验证结果');
            $table->string('ip_address', 45)->nullable()->comment('操作IP');
            $table->timestamps();

            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_config_histories');
    }
};
