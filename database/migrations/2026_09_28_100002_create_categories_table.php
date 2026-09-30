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
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('image', 500)->nullable();
            $table->text('description')->nullable();
            $table->uuid('parent_id')->nullable()->index();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false)->index();
            $table->boolean('is_returnable')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
