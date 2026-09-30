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
        Schema::create('stores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('logo_url')->nullable();
            $table->string('banner_url')->nullable();
            $table->string('store_name');
            $table->boolean('is_online')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_active')->default(false);
            $table->enum('store_type', ['INTERNAL', 'EXTERNAL']);
            $table->timestamps();

            $table->index(['is_approved', 'is_active'], 'idx_stores_approved_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
