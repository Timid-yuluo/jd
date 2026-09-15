<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->unique('payment_no', 'orders_payment_no_unique');
        });

        Schema::table('credit_pack_orders', function (Blueprint $table): void {
            $table->unique('payment_no', 'credit_pack_orders_payment_no_unique');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropUnique('orders_payment_no_unique');
        });

        Schema::table('credit_pack_orders', function (Blueprint $table): void {
            $table->dropUnique('credit_pack_orders_payment_no_unique');
        });
    }
};
