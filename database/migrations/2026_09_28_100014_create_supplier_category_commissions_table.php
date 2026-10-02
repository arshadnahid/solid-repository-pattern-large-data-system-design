<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-supplier commission overrides by category. One row per (supplier, category).
     */
    public function up(): void
    {
        Schema::create('supplier_category_commissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('categories')->cascadeOnDelete();
            $table->decimal('commission_percentage', 20, 2);
            $table->timestamps();

            $table->unique(['supplier_id', 'category_id'], 'supplier_category_commissions_supplier_category_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_category_commissions');
    }
};
