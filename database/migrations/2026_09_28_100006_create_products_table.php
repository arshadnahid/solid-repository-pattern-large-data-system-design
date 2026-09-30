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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('bin_no', 500);
            $table->string('name', 500);
            $table->string('slug', 500)->index();
            $table->string('unit', 50)->nullable();
            $table->uuid('store_id')->nullable();
            // No suppliers table yet, so this is indexed but not constrained.
            $table->uuid('supplier_id')->nullable()->index();
            $table->foreignUuid('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->string('short_description', 1000)->nullable();
            $table->text('description')->nullable();
            $table->integer('low_stock')->nullable();
            $table->integer('minimum_order_qty')->nullable();
            $table->boolean('is_digital')->default(false);
            $table->boolean('is_fragile')->default(false);
            $table->boolean('is_rotting')->default(false);
            $table->boolean('is_special')->default(false);
            $table->boolean('is_published')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_dropshipper_product')->default(false);
            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('weight', 10, 2)->nullable();
            $table->decimal('shipping_cost', 20, 2)->nullable();
            $table->enum('shipping_cost_type', ['free', 'fixed', 'quantityMultiply'])->nullable();
            $table->decimal('shipping_cost_dropshipper', 20, 2)->nullable();
            $table->enum('shipping_cost_type_dropshipper', ['free', 'fixed', 'quantityMultiply'])->nullable();
            $table->json('note_for_seller')->nullable();
            $table->timestamps();

            // Leading store_id also serves store-only lookups.
            $table->unique(['store_id', 'slug'], 'products_store_slug_unique');
            $table->index(
                ['is_active', 'is_published', 'is_dropshipper_product', 'created_at'],
                'products_status_created_index',
            );
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
