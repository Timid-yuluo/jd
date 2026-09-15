<?php

declare(strict_types=1);

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
        Schema::table('admin_notifications', function (Blueprint $table) {
            $table->timestamp('scheduled_at')->nullable()->after('sent_at');  // 计划发送时间
            $table->string('channel')->default('both')->after('type');         // 发送渠道: site, email, both
            $table->foreignId('email_template_id')->nullable()->after('channel')->constrained('email_templates')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('admin_notifications', function (Blueprint $table) {
            $table->dropColumn(['scheduled_at', 'channel']);
            $table->dropForeign(['email_template_id']);
            $table->dropColumn('email_template_id');
        });
    }
};
