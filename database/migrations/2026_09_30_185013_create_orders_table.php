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
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_code')->unique();
            $table->timestamp('order_date')->useCurrent();
            $table->jsonb('shipping_address');

            $table->decimal('total_amount', 20, 2)->default(0);
            $table->decimal('total_shipping_cost', 20, 2)->default(0);
            $table->decimal('total_tax', 20, 2)->default(0);
            $table->decimal('total_discount', 20, 2)->default(0);
            $table->decimal('total_commission', 20, 2)->default(0);
            $table->decimal('total_margin', 20, 2)->default(0);

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Mirrors roleTypeEnum from auth schema; adjust values if needed.
            $table->string('user_type', 50)->default('CUSTOMER');

            $table->enum('payment_status', [
                'pending',
                'unsuccessful',
                'paid',
                'refund_initiated',
                'refunded',
                'partially_refunded',
            ])->default('pending');

            $table->enum('order_delivery_status', [
                'pending',
                'partially_delivered',
                'fully_delivered',
                'cancelled',
            ])->default('pending');

            $table->string('stripe_payment_method_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable()->index();
            $table->string('stripe_payment_charge_id')->nullable();
            $table->boolean('is_3ds_verified')->default(false);

            $table->jsonb('geo_location')->nullable();
            $table->string('ip_address')->nullable();
            $table->jsonb('shipping_rules')->nullable();

            $table->timestamps();

            $table->index('payment_status');
            $table->index('order_delivery_status');
            $table->index('order_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
