<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_pack_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 32)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('credit_pack_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('amount');
            $table->enum('status', ['pending', 'paid', 'refunded', 'expired'])->default('pending');
            $table->string('payment_method', 32)->nullable();
            $table->string('payment_no', 64)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_pack_orders');
    }
};
