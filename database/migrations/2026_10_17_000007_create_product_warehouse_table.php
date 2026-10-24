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
        Schema::create('product_warehouse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->decimal('quantity', 12, 3)->default(0.000);
            $table->decimal('reserved_quantity', 12, 3)->default(0.000);
            $table->decimal('weighted_average_cost', 15, 4)->default(0.0000);
            $table->timestamps();

            $table->unique(['product_id', 'warehouse_id'], 'unique_product_in_warehouse');
            $table->index(['warehouse_id', 'quantity'], 'idx_warehouse_qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_warehouse');
    }
};
