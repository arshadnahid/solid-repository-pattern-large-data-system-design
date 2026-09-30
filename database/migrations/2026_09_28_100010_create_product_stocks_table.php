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
        Schema::create('product_stocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku')->nullable()->unique();
            $table->string('attribute_value', 500)->nullable();
            $table->json('attribute')->nullable();
            $table->decimal('purchase_price', 20, 2);
            $table->decimal('margin', 20, 2)->nullable();
            $table->decimal('sales_price', 20, 2);
            $table->decimal('discount', 20, 2)->nullable();
            $table->enum('discount_type', ['flat', 'percent'])->nullable();
            $table->date('discount_start_date')->nullable();
            $table->date('discount_end_date')->nullable();
            $table->decimal('dropshipper_price', 20, 2)->nullable()->index();
            $table->decimal('dropshipper_discount', 20, 2)->nullable();
            $table->enum('dropshipper_discount_type', ['flat', 'percent'])->nullable();
            $table->date('dropshipper_discount_start_date')->nullable();
            $table->date('dropshipper_discount_end_date')->nullable();
            $table->integer('stock_qty')->default(0);
            $table->string('thumbnail_image', 1000)->nullable();
            $table->json('gallery_images')->nullable();
            $table->boolean('is_default')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();

            $table->index(['product_id', 'sales_price']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_stocks');
    }
};
