<?php

namespace App\Features\Balance\Http\Controllers;

use App\Features\Balance\Services\AssetService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AssetController extends Controller
{
    protected AssetService $assetService;

    public function __construct(AssetService $assetService)
    {
        $this->assetService = $assetService;
    }

    /**
     * Get all assets for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'error' => 'User not authenticated'
                ], 401);
            }

            $assets = $this->assetService->getUserAssets($user->id);

            return response()->json([
                'data' => $assets->map(function ($asset) {
                    return [
                        'id' => $asset->id,
                        'symbol' => $asset->symbol,
                        'amount' => number_format($asset->amount, 8, '.', ''),
                        'locked_amount' => number_format($asset->locked_amount, 8, '.', ''),
                        'available_amount' => number_format($asset->available_amount, 8, '.', ''),
                        'created_at' => $asset->created_at->toISOString(),
                        'updated_at' => $asset->updated_at->toISOString(),
                    ];
                })
            ]);
        } catch (\Exception $e) {
            \Log::error('Assets fetch error: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to fetch assets',
                'message' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get user's complete balance information including USD and assets.
     */
    public function balance(Request $request): JsonResponse
    {
        $balance = $this->assetService->getUserCompleteBalance($request->user()->id);

        return response()->json([
            'data' => [
                'usd_balance' => number_format($balance['usd_balance'], 2, '.', ''),
                'assets' => $balance['assets'],
                'total_asset_value' => number_format($balance['total_asset_value'], 2, '.', ''),
                'total_portfolio_value' => number_format($balance['total_portfolio_value'], 2, '.', ''),
            ]
        ]);
    }

    /**
     * Get user's portfolio summary.
     */
    public function portfolio(Request $request): JsonResponse
    {
        $summary = $this->assetService->getPortfolioSummary($request->user()->id);

        return response()->json([
            'data' => $summary
        ]);
    }

    /**
     * Add test assets for development/testing purposes.
     * This endpoint should only be available in local development.
     */
    public function addTestAssets(Request $request): JsonResponse
    {
        // Only allow in local development
        if (app()->environment() !== 'local') {
            return response()->json(['error' => 'This endpoint is only available in local development'], 403);
        }

        $validated = $request->validate([
            'symbol' => 'required|string|in:BTC,ETH',
            'amount' => 'required|numeric|min:0.00000001|max:10000',
        ]);

        $success = $this->assetService->depositAsset(
            $request->user()->id,
            $validated['symbol'],
            $validated['amount']
        );

        if ($success) {
            return response()->json([
                'message' => "Successfully added {$validated['amount']} {$validated['symbol']} to your account",
                'data' => [
                    'symbol' => $validated['symbol'],
                    'amount_added' => $validated['amount'],
                ]
            ]);
        }

        return response()->json(['error' => 'Failed to add test assets'], 500);
    }
}
