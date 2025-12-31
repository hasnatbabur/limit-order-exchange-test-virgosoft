<?php

namespace App\Console\Commands;

use App\Features\Balance\Services\AssetService;
use App\Models\User;
use Illuminate\Console\Command;

class InitializeAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assets:initialize {--user-id= : Initialize assets for specific user ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize default assets for users who don\'t have them';

    /**
     * Execute the console command.
     */
    public function handle(AssetService $assetService): int
    {
        $userId = $this->option('user-id');

        if ($userId) {
            // Initialize for specific user
            $user = User::find($userId);

            if (!$user) {
                $this->error("User with ID {$userId} not found.");
                return Command::FAILURE;
            }

            $existingAssets = \App\Features\Balance\Models\Asset::where('user_id', $userId)->count();

            if ($existingAssets > 0) {
                $this->info("User {$user->email} already has {$existingAssets} assets.");
                return Command::SUCCESS;
            }

            $assetService->initializeUserAssets($userId);
            $this->info("Assets initialized for user: {$user->email}");

        } else {
            // Initialize for all users without assets
            $users = User::all();
            $initializedCount = 0;

            foreach ($users as $user) {
                $existingAssets = \App\Features\Balance\Models\Asset::where('user_id', $user->id)->count();

                if ($existingAssets == 0) {
                    $assetService->initializeUserAssets($user->id);
                    $this->info("Assets initialized for user: {$user->email}");
                    $initializedCount++;
                }
            }

            if ($initializedCount === 0) {
                $this->info('All users already have assets initialized.');
            } else {
                $this->info("Assets initialized for {$initializedCount} users.");
            }
        }

        return Command::SUCCESS;
    }
}
