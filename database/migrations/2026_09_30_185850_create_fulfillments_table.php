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
        Schema::create('fulfillments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();

            $table->enum('source_type', ['external', 'internal']);
            $table->foreignUuid('store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignUuid('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();

            $table->enum('delivery_status', [
                'pending',
                'order_placed',
                'confirmed',
                'picked_up',
                'on_the_way',
                'returned_delivery',
                'delivered',
                'reshipped',
                'cancelled',
            ])->default('pending');
            $table->timestamp('delivered_at')->nullable();

            $table->decimal('total_order_amount', 20, 2)->default(0);
            $table->decimal('total_order_shipping_cost', 20, 2)->default(0);
            $table->decimal('total_discount', 20, 2)->default(0);
            $table->decimal('fulfillment_tax_amount', 20, 2)->default(0);
            $table->decimal('total_fulfillment_commission', 20, 2)->default(0);
            $table->decimal('total_fulfillment_margin', 20, 2)->default(0);

            // No coupons table yet, so this is indexed but not constrained.
            $table->uuid('coupon_id')->nullable()->index();

            $table->string('carrier_name')->nullable();
            $table->string('tracking_number')->nullable();
            $table->text('tracking_link')->nullable();
            $table->string('cancel_reason')->nullable();
            $table->string('reship_reason')->nullable();
            $table->unsignedInteger('reship_count')->default(0);

            $table->timestamps();

            $table->index('delivery_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fulfillments');
    }
};
