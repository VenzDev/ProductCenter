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
        Schema::table('product_attachments', function (Blueprint $table) {
            $table->dropIndex(['product_id', 'locale']);
            $table->dropColumn('locale');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_attachments', function (Blueprint $table) {
            $table->dropIndex(['product_id']);
            $table->string('locale', 5)->nullable();
            $table->index(['product_id', 'locale']);
        });
    }
};
