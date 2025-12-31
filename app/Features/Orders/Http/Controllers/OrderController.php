<?php

namespace App\Features\Orders\Http\Controllers;

use App\Features\Orders\Enums\OrderSide;
use App\Features\Orders\Http\Requests\CreateOrderRequest;
use App\Features\Orders\Models\Order;
use App\Features\Orders\Services\OrderService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    protected OrderService $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Get all orders for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'symbol' => 'sometimes|string|in:BTC-USD,ETH-USD',
            'status' => 'sometimes|string|in:open,filled,cancelled',
            'side' => 'sometimes|string|in:buy,sell',
            'date_range' => 'sometimes|string|in:today,week,month,all',
        ]);

        $perPage = $request->get('per_page', 10);
        $filters = $request->only(['symbol', 'status', 'side', 'date_range']);

        $paginatedOrders = $this->orderService->getPaginatedUserOrders($request->user(), $filters, $perPage);

        return response()->json([
            'orders' => $paginatedOrders->map(function ($order) {
                return [
                    'id' => $order->id,
                    'symbol' => $order->symbol,
                    'side' => $order->side->value,
                    'price' => number_format($order->price, 8, '.', ''),
                    'amount' => number_format($order->amount, 8, '.', ''),
                    'filled_amount' => number_format($order->filled_amount, 8, '.', ''),
                    'status' => $order->status->value,
                    'total_value' => number_format($order->total_value, 2, '.', ''),
                    'created_at' => $order->created_at->toISOString(),
                    'updated_at' => $order->updated_at->toISOString(),
                ];
            }),
            'pagination' => [
                'current_page' => $paginatedOrders->currentPage(),
                'last_page' => $paginatedOrders->lastPage(),
                'per_page' => $paginatedOrders->perPage(),
                'total' => $paginatedOrders->total(),
                'from' => $paginatedOrders->firstItem(),
                'to' => $paginatedOrders->lastItem(),
            ]
        ]);
    }

    /**
     * Create a new order.
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder(
                $request->user(),
                $request->validated()
            );

            return response()->json([
                'message' => 'Order created successfully',
                'order' => [
                    'id' => $order->id,
                    'symbol' => $order->symbol,
                    'side' => $order->side->value,
                    'price' => number_format($order->price, 8, '.', ''),
                    'amount' => number_format($order->amount, 8, '.', ''),
                    'status' => $order->status->value,
                    'total_value' => number_format($order->total_value, 2, '.', ''),
                    'created_at' => $order->created_at->toISOString(),
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get order book for a symbol.
     */
    public function orderBook(Request $request): JsonResponse
    {
        $request->validate([
            'symbol' => 'required|string|in:BTC-USD,ETH-USD',
            'limit' => 'sometimes|integer|min:1|max:100'
        ]);

        $limit = $request->get('limit', 20);
        $orderBook = $this->orderService->getOrderBook($request->symbol, $limit);

        return response()->json([
            'symbol' => $request->symbol,
            'buy_orders' => $orderBook['buy_orders']->map(function ($order) {
                return [
                    'id' => $order->id,
                    'price' => number_format($order->price, 2, '.', ''),
                    'amount' => number_format($order->remaining_amount, 8, '.', ''),
                    'total' => number_format($order->price * $order->remaining_amount, 2, '.', ''),
                ];
            }),
            'sell_orders' => $orderBook['sell_orders']->map(function ($order) {
                return [
                    'id' => $order->id,
                    'price' => number_format($order->price, 2, '.', ''),
                    'amount' => number_format($order->remaining_amount, 8, '.', ''),
                    'total' => number_format($order->price * $order->remaining_amount, 2, '.', ''),
                ];
            }),
        ]);
    }

    /**
     * Cancel an order.
     */
    public function cancel(Order $order): JsonResponse
    {
        if ($order->user_id !== request()->user()->id) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $cancelledOrder = $this->orderService->cancelOrder($order);

            return response()->json([
                'message' => 'Order cancelled successfully',
                'order' => [
                    'id' => $cancelledOrder->id,
                    'status' => $cancelledOrder->status->value,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel order',
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
