<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
            $table->foreignId('category_id')->constrained();
            $table->jsonb('name');
            $table->jsonb('description')->nullable();
            $table->integer('price_cents');
            $table->char('currency', 3)->default('PLN');
            $table->jsonb('attributes')->nullable();
            $table->string('main_image')->nullable();
            $table->timestamps();

            $table->index('category_id');
        });

        // GIN index enables filtering by attributes (e.g. attributes->>'weight_kg'); sqlite (used in
        // tests) has no such concept, so this only runs against the real Postgres connection.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX products_attributes_gin_idx ON products USING GIN (attributes)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
