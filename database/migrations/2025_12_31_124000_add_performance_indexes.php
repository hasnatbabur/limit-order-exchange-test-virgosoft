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
        Schema::table('orders', function (Blueprint $table) {
            // Indexes for order book queries
            $schema = \Illuminate\Support\Facades\DB::connection()->getSchemaBuilder();

            // Check if index exists before creating
            if (!$schema->hasIndex('orders', 'orders_symbol_status_side_price_index')) {
                $table->index(['symbol', 'status', 'side', 'price']);
            }

            if (!$schema->hasIndex('orders', 'orders_user_id_status_index')) {
                $table->index(['user_id', 'status']);
            }
        });

        Schema::table('assets', function (Blueprint $table) {
            // Indexes for asset queries
            $schema = \Illuminate\Support\Facades\DB::connection()->getSchemaBuilder();

            // Check if index exists before creating
            if (!$schema->hasIndex('assets', 'assets_user_id_symbol_index')) {
                $table->index(['user_id', 'symbol']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $schema = \Illuminate\Support\Facades\DB::connection()->getSchemaBuilder();

            // Check if index exists before dropping
            if ($schema->hasIndex('orders', 'orders_symbol_status_side_price_index')) {
                $table->dropIndex(['symbol', 'status', 'side', 'price']);
            }

            if ($schema->hasIndex('orders', 'orders_user_id_status_index')) {
                $table->dropIndex(['user_id', 'status']);
            }
        });

        Schema::table('assets', function (Blueprint $table) {
            $schema = \Illuminate\Support\Facades\DB::connection()->getSchemaBuilder();

            // Check if index exists before dropping
            if ($schema->hasIndex('assets', 'assets_user_id_symbol_index')) {
                $table->dropIndex(['user_id', 'symbol']);
            }
        });
    }
};
