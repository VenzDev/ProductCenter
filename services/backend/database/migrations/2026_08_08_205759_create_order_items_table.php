<?php

declare(strict_types=1);

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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained(); // no cascade: keep order history if product is removed
            $table->string('product_name'); // snapshot at purchase time, plain string (not translatable JSONB)
            $table->integer('quantity')->default(1);
            $table->integer('unit_price_cents'); // snapshot at purchase time, not live products.price_cents
            $table->timestamp('created_at')->useCurrent();

            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
