<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-seller commission overrides by category. One row per (seller, category).
     */
    public function up(): void
    {
        Schema::create('seller_category_commissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // No seller_profiles table yet, so this is not constrained.
            $table->uuid('seller_profile_id');
            $table->foreignUuid('category_id')->constrained('categories')->cascadeOnDelete();
            $table->decimal('commission_percentage', 20, 2);
            $table->timestamps();

            $table->unique(['seller_profile_id', 'category_id'], 'seller_category_commissions_seller_category_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_category_commissions');
    }
};
