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
        Schema::create('fulfillment_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('fulfillment_id')->constrained('fulfillments')->cascadeOnDelete();

            // Intentionally NOT a DB foreign key: product_stock rows can be replaced/deleted
            // as products are edited, and order history must not be affected.
            // The relation is still defined on the Eloquent model.
            $table->uuid('product_stock_id')->index();

            $table->unsignedInteger('qty')->default(1);
            $table->decimal('unit_price', 20, 2)->default(0);
            $table->decimal('fulfillment_item_tax_amount', 20, 2)->default(0);

            $table->string('transaction_type', 50)->nullable();
            $table->decimal('transaction_percentage', 20, 2)->nullable()->default(0);
            $table->decimal('transaction_amount_per_item', 20, 2)->nullable()->default(0);
            $table->decimal('transaction_amount_in_total', 20, 2)->nullable()->default(0);
            $table->boolean('is_payout')->nullable()->default(false);

            // Snapshot of product / product stock at order time.
            $table->string('product_name', 500)->nullable();
            $table->string('sku')->nullable();
            $table->string('attribute_value', 500)->nullable();
            $table->string('thumbnail_image', 1000)->nullable();
            $table->decimal('purchase_price', 20, 2)->nullable();
            $table->decimal('margin', 20, 2)->nullable();
            $table->decimal('sales_price', 20, 2)->nullable();
            $table->decimal('discount', 20, 2)->nullable();
            // Mirrors discountTypeEnum from product schema.
            $table->string('discount_type', 20)->nullable();
            $table->decimal('dropshipper_price', 20, 2)->nullable();
            $table->decimal('dropshipper_discount', 20, 2)->nullable();
            $table->string('dropshipper_discount_type', 20)->nullable();

            $table->foreignUuid('invoice_transaction_id')
                ->nullable()
                ->constrained('invoice_transactions')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('is_payout');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fulfillment_items');
    }
};
