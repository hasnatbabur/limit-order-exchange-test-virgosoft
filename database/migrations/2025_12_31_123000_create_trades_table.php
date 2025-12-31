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
        Schema::create('trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('buy_order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('sell_order_id')->constrained('orders')->onDelete('cascade');
            $table->string('symbol', 20); // e.g., BTC-USD, ETH-USD
            $table->decimal('price', 18, 8); // Trade price with high precision
            $table->decimal('amount', 18, 8); // Amount traded with high precision
            $table->decimal('commission', 18, 8); // Commission deducted from seller
            $table->timestamps();

            // Indexes for performance
            $table->index(['symbol', 'created_at']);
            $table->index('buy_order_id');
            $table->index('sell_order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trades');
    }
};
