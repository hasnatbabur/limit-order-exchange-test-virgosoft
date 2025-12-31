<?php

namespace App\Features\Orders\Http\Controllers;

use App\Features\Orders\Models\Trade;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TradeController extends Controller
{
    /**
     * Get user's trade history.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get query parameters
        $symbol = $request->query('symbol');
        $limit = min($request->query('limit', 50), 100); // Max 100 records

        // Build query
        $query = Trade::query()
            ->with(['buyOrder', 'sellOrder'])
            ->forUser($user->id);

        // Filter by symbol if provided
        if ($symbol) {
            $query->forSymbol($symbol);
        }

        // Get recent trades
        $trades = $query->recent($limit)->get();

        // Store user ID for use in closure
        $userId = $user->id;

        return response()->json([
            'data' => $trades->map(function ($trade) use ($userId) {
                return [
                    'id' => $trade->id,
                    'symbol' => $trade->symbol,
                    'price' => $trade->price,
                    'amount' => $trade->amount,
                    'total_value' => $trade->getTotalValueAttribute(),
                    'commission' => $trade->commission,
                    'seller_net_value' => $trade->getSellerNetValueAttribute(),
                    'created_at' => $trade->created_at->toISOString(),
                    'buy_order_id' => $trade->buy_order_id,
                    'sell_order_id' => $trade->sell_order_id,
                    'was_buyer' => $trade->buyOrder->user_id === $userId,
                    'was_seller' => $trade->sellOrder->user_id === $userId,
                ];
            }),
            'meta' => [
                'total' => $trades->count(),
                'symbol' => $symbol ?: 'all',
            ]
        ]);
    }

    /**
     * Get recent trades for a symbol (public endpoint).
     */
    public function recent(Request $request): JsonResponse
    {
        $symbol = $request->query('symbol');
        $limit = min($request->query('limit', 20), 50); // Max 50 records for public endpoint

        if (!$symbol) {
            return response()->json([
                'error' => 'Symbol parameter is required'
            ], 400);
        }

        // Get recent trades for symbol
        $trades = Trade::query()
            ->forSymbol($symbol)
            ->recent($limit)
            ->get();

        return response()->json([
            'data' => $trades->map(function ($trade) {
                return [
                    'id' => $trade->id,
                    'symbol' => $trade->symbol,
                    'price' => $trade->price,
                    'amount' => $trade->amount,
                    'total_value' => $trade->getTotalValueAttribute(),
                    'created_at' => $trade->created_at->toISOString(),
                ];
            }),
            'meta' => [
                'total' => $trades->count(),
                'symbol' => $symbol,
            ]
        ]);
    }
}
