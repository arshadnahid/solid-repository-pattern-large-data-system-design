<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Columns the create-order payload carries that the orders table had no place for.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->char('currency', 3)->default('USD')->after('order_date');
            $table->jsonb('billing_address')->nullable()->after('shipping_address');
            $table->string('payment_provider', 30)->default('stripe')->after('order_delivery_status');
            $table->text('customer_note')->nullable()->after('shipping_rules');
            $table->string('user_agent', 512)->nullable()->after('ip_address');
            $table->string('platform', 20)->nullable()->after('user_agent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['currency', 'billing_address', 'payment_provider', 'customer_note', 'user_agent', 'platform']);
        });
    }
};
