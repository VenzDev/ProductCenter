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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->string('status', 20)->default('pending');
            $table->integer('total_cents');
            $table->char('currency', 3)->default('PLN');
            $table->string('payment_reference')->nullable()->unique(); // Stripe session/payment intent id
            $table->timestamps();

            $table->index('user_id');
            $table->index('status');
        });

        // CHECK constraint, not a native Postgres ENUM: adding a new status later is a plain
        // ALTER TABLE, not ALTER TYPE. Sqlite (used in tests) enforces status at the app level instead.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status IN ('pending', 'paid', 'failed', 'cancelled'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
