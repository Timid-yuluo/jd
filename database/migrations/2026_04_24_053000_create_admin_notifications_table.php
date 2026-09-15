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
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->text('content');
            $table->enum('type', ['announcement', 'maintenance', 'feature', 'warning'])->default('announcement');
            $table->enum('target_type', ['all', 'roles', 'users'])->default('all');
            $table->json('target_roles')->nullable();
            $table->json('target_users')->nullable();
            $table->foreignId('sender_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('read_count')->default(0);
            $table->timestamps();

            $table->index('type');
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
    }
};
