<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_bookmarks', function (Blueprint $table) {
            $table->text('note')->nullable()->after('job_description');
            $table->string('tags', 500)->nullable()->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('job_bookmarks', function (Blueprint $table) {
            $table->dropColumn(['note', 'tags']);
        });
    }
};
