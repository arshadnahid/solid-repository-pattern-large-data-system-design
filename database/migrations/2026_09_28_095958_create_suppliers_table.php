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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('email')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('address')->nullable();
            $table->string('logo_url')->nullable();
            $table->decimal('transaction_percentage', 20, 2)->nullable()->default(0);
            $table->enum('transaction_type', ['MARGIN', 'COMMISSION'])->nullable();
            // No admins table yet, so these are indexed but not constrained.
            $table->unsignedBigInteger('created_by_id')->nullable()->index();
            $table->unsignedBigInteger('kam_id')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
