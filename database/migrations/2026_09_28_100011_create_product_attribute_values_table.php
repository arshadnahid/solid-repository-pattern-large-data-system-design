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
        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->foreignUuid('product_stock_id')->constrained('product_stocks')->cascadeOnDelete();
            $table->foreignUuid('attribute_id')->constrained('attributes')->cascadeOnDelete();
            $table->foreignUuid('attribute_value_id')->constrained('attribute_values')->cascadeOnDelete();

            $table->primary(['product_stock_id', 'attribute_id', 'attribute_value_id'], 'product_attribute_values_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_attribute_values');
    }
};
