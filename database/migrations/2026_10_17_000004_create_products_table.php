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
            $table->id();
            $table->string('sku', 50)->unique();
            $table->string('barcode', 50)->unique()->nullable();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->foreignId('category_id')
                ->constrained('categories')
                ->restrictOnDelete();
            $table->foreignId('unit_id')
                ->constrained('units')
                ->restrictOnDelete();
            $table->decimal('min_reorder_level', 12, 3)->default(0.000);
            $table->decimal('default_sale_price', 15, 4)->default(0.0000);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['category_id', 'is_active']);
            $table->index(['sku', 'barcode']);
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
