<?php

namespace Tests\Feature;

use App\Models\User;
use App\Features\Balance\Models\Asset;
use App\Features\Balance\Services\AssetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetFaultToleranceTest extends TestCase
{
    use RefreshDatabase;

    protected AssetService $assetService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assetService = app(AssetService::class);
    }

    /**
     * Test that assets are automatically created when accessed for the first time.
     */
    public function test_assets_are_created_automatically_on_first_access(): void
    {
        $user = User::factory()->create(['balance' => 1000.00]);

        // Initially, user should have no assets
        $this->assertEquals(0, $user->assets()->count());

        // Access BTC asset - should be created automatically
        $btcAsset = $this->assetService->getUserAssetBySymbol($user->id, 'BTC');

        $this->assertNotNull($btcAsset);
        $this->assertEquals('BTC', $btcAsset->symbol);
        $this->assertEquals(0.0, $btcAsset->amount);
        $this->assertEquals(0.0, $btcAsset->locked_amount);
        $this->assertEquals(1, $user->assets()->count());

        // Access ETH asset - should also be created automatically
        $ethAsset = $this->assetService->getUserAssetBySymbol($user->id, 'ETH');

        $this->assertNotNull($ethAsset);
        $this->assertEquals('ETH', $ethAsset->symbol);
        $this->assertEquals(0.0, $ethAsset->amount);
        $this->assertEquals(0.0, $ethAsset->locked_amount);
        $this->assertEquals(2, $user->assets()->count());
    }

    /**
     * Test that accessing unsupported asset throws exception.
     */
    public function test_unsupported_asset_throws_exception(): void
    {
        $user = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Asset symbol 'INVALID' is not supported or enabled");

        $this->assetService->getUserAssetBySymbol($user->id, 'INVALID');
    }

    /**
     * Test that disabled assets cannot be accessed.
     */
    public function test_disabled_assets_cannot_be_accessed(): void
    {
        $user = User::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Asset symbol 'USDT' is not supported or enabled");

        $this->assetService->getUserAssetBySymbol($user->id, 'USDT');
    }

    /**
     * Test that deposit works with auto-creation.
     */
    public function test_deposit_works_with_auto_creation(): void
    {
        $user = User::factory()->create();

        // Deposit to non-existent asset - should create it automatically
        $result = $this->assetService->depositAsset($user->id, 'BTC', 0.5);

        $this->assertTrue($result);

        $asset = $this->assetService->getUserAssetBySymbol($user->id, 'BTC');
        $this->assertNotNull($asset);
        $this->assertEquals(0.5, $asset->amount);
    }

    /**
     * Test that withdraw works with auto-creation but fails for insufficient funds.
     */
    public function test_withdraw_fails_for_insufficient_funds(): void
    {
        $user = User::factory()->create();

        // Try to withdraw from non-existent asset - should create it but fail due to insufficient funds
        $result = $this->assetService->withdrawAsset($user->id, 'BTC', 1.0);

        $this->assertFalse($result);

        $asset = $this->assetService->getUserAssetBySymbol($user->id, 'BTC');
        $this->assertNotNull($asset);
        $this->assertEquals(0.0, $asset->amount);
    }

    /**
     * Test that asset locking works with auto-creation.
     */
    public function test_asset_locking_works_with_auto_creation(): void
    {
        $user = User::factory()->create();

        // First deposit some funds
        $this->assetService->depositAsset($user->id, 'BTC', 1.0);

        // Lock assets - should work
        $result = $this->assetService->lockAssetsForSellOrder($user->id, 'BTC', 0.5);
        $this->assertTrue($result);

        $asset = $this->assetService->getUserAssetBySymbol($user->id, 'BTC');
        $this->assertEquals(0.5, $asset->locked_amount);
        $this->assertEquals(0.5, $asset->available_amount);
    }

    /**
     * Test concurrent access to same missing asset creates only one record.
     */
    public function test_concurrent_access_creates_single_asset(): void
    {
        $user = User::factory()->create();

        // Simulate concurrent access by calling the method multiple times
        $asset1 = $this->assetService->getUserAssetBySymbol($user->id, 'BTC');
        $asset2 = $this->assetService->getUserAssetBySymbol($user->id, 'BTC');
        $asset3 = $this->assetService->getUserAssetBySymbol($user->id, 'BTC');

        // All should return the same asset
        $this->assertEquals($asset1->id, $asset2->id);
        $this->assertEquals($asset2->id, $asset3->id);

        // Only one asset record should exist
        $this->assertEquals(1, Asset::where('user_id', $user->id)->where('symbol', 'BTC')->count());
    }

    /**
     * Test that user complete balance includes auto-created assets.
     */
    public function test_user_complete_balance_includes_auto_created_assets(): void
    {
        $user = User::factory()->create(['balance' => 1000.00]);

        // Access assets to trigger auto-creation
        $this->assetService->getUserAssetBySymbol($user->id, 'BTC');
        $this->assetService->getUserAssetBySymbol($user->id, 'ETH');

        $completeBalance = $this->assetService->getUserCompleteBalance($user->id);

        $this->assertEquals(1000.00, $completeBalance['usd_balance']);
        $this->assertEquals(2, $completeBalance['assets']->count());
        $this->assertArrayHasKey('BTC', $completeBalance['assets']->keyBy('symbol')->toArray());
        $this->assertArrayHasKey('ETH', $completeBalance['assets']->keyBy('symbol')->toArray());
    }
}
