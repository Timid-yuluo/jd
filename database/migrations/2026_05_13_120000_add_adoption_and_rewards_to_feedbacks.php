<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('feedbacks')) {
            Schema::table('feedbacks', function (Blueprint $table): void {
                if (! Schema::hasColumn('feedbacks', 'adoption_status')) {
                    $table->string('adoption_status', 20)->default('pending')->after('status');
                    $table->index('adoption_status');
                }

                if (! Schema::hasColumn('feedbacks', 'adopted_at')) {
                    $table->timestamp('adopted_at')->nullable()->after('admin_note');
                }

                if (! Schema::hasColumn('feedbacks', 'adopted_by')) {
                    $table->foreignId('adopted_by')->nullable()->after('adopted_at')->constrained('users')->nullOnDelete();
                }

                if (! Schema::hasColumn('feedbacks', 'adoption_note')) {
                    $table->text('adoption_note')->nullable()->after('adopted_by');
                }
            });
        }

        if (! Schema::hasTable('feedback_rewards')) {
            Schema::create('feedback_rewards', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('feedback_id')->constrained('feedbacks')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_credit_id')->nullable()->constrained('user_credits')->nullOnDelete();
                $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('quota_key', 64)->nullable();
                $table->unsignedInteger('credits');
                $table->unsignedInteger('validity_days')->nullable();
                $table->string('reason', 500)->nullable();
                $table->timestamp('granted_at');
                $table->timestamps();

                $table->unique('feedback_id');
                $table->index(['user_id', 'granted_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_rewards');

        if (Schema::hasTable('feedbacks')) {
            Schema::table('feedbacks', function (Blueprint $table): void {
                if (Schema::hasColumn('feedbacks', 'adopted_by')) {
                    $table->dropForeign(['adopted_by']);
                }

                $columns = array_values(array_filter([
                    Schema::hasColumn('feedbacks', 'adoption_status') ? 'adoption_status' : null,
                    Schema::hasColumn('feedbacks', 'adopted_at') ? 'adopted_at' : null,
                    Schema::hasColumn('feedbacks', 'adopted_by') ? 'adopted_by' : null,
                    Schema::hasColumn('feedbacks', 'adoption_note') ? 'adoption_note' : null,
                ]));

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
