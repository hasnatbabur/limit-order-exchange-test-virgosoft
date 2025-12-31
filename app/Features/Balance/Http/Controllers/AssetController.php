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
        $assets = $this->assetService->getUserAssets($request->user()->id);

        return response()->json([
            'data' => $assets->map(function ($asset) {
                return [
                    'id' => $asset->id,
                    'symbol' => $asset->symbol,
                    'amount' => number_format($asset->amount, 8, '.', ''),
                    'locked_amount' => number_format($asset->locked_amount, 8, '.', ''),
                    'available_amount' => number_format($asset->getAvailableAmount(), 8, '.', ''),
                    'created_at' => $asset->created_at->toISOString(),
                    'updated_at' => $asset->updated_at->toISOString(),
                ];
            })
        ]);
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
}
