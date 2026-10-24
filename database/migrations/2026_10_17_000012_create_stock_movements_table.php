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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->string('movement_type', 50); // purchase, purchase_return, sale, sale_return, transfer_out, transfer_in, adjustment_in, adjustment_out
            $table->string('reference_type', 100);
            $table->unsignedBigInteger('reference_id');
            $table->decimal('quantity', 12, 3); // signed: positive for incoming, negative for outgoing
            $table->decimal('before_quantity', 12, 3);
            $table->decimal('after_quantity', 12, 3);
            $table->decimal('unit_cost', 15, 4);
            $table->decimal('total_cost', 15, 4);
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['product_id', 'warehouse_id', 'created_at'], 'idx_stock_ledger');
            $table->index(['reference_type', 'reference_id'], 'idx_stock_reference');
            $table->index(['movement_type', 'created_at'], 'idx_stock_type_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
