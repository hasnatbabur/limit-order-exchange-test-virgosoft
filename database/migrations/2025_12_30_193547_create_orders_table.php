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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('symbol'); // e.g., BTC-USD, ETH-USD
            $table->string('side'); // 'buy' or 'sell' - handled by PHP enum
            $table->decimal('price', 18, 8); // Price with high precision
            $table->decimal('amount', 18, 8); // Amount with high precision
            $table->decimal('filled_amount', 18, 8)->default(0); // Amount that has been filled
            $table->string('status')->default('open'); // 'open', 'filled', 'cancelled' - handled by PHP enum
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['symbol', 'status', 'side']);
            $table->index(['price', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
