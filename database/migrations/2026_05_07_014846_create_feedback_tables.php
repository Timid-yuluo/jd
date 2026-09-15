<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visitor_email')->nullable();
            $table->string('page_url')->nullable();
            $table->string('page_name', 100)->nullable();
            $table->enum('category', ['bug', 'suggestion', 'ux', 'other'])->default('other');
            $table->string('title');
            $table->text('content');
            $table->enum('status', ['pending', 'processing', 'replied', 'closed'])->default('pending');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->text('admin_note')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('category');
            $table->index('created_at');
        });

        Schema::create('feedback_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_admin')->default(false);
            $table->text('content');
            $table->timestamps();

            $table->index('feedback_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_replies');
        Schema::dropIfExists('feedbacks');
    }
};
