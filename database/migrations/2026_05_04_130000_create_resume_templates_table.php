<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('resume_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->string('name', 120);
            $table->string('category', 40)->index();
            $table->string('position', 60)->index();
            $table->string('level', 30)->default('社招1-3年')->index();
            $table->string('industry', 60)->nullable()->index();
            $table->string('style', 30)->default('专业');
            $table->string('template', 20)->default('classic');
            $table->string('theme', 20)->default('blue');
            $table->string('ats_level', 20)->default('A')->index();
            $table->string('density', 20)->default('中');
            $table->json('tags')->nullable();
            $table->json('font_settings')->nullable();
            $table->json('module_blueprint')->nullable();
            $table->string('preview_image_url', 255)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedInteger('usage_count')->default(0);
            $table->unsignedSmallInteger('sort_order')->default(100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('resume_templates');
    }
};
