<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Asset;
use App\Services\AssetSyncService;
use App\Services\Brapi\BrapiException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncAssetsCommand extends Command
{
    protected $signature = 'assets:sync';

    protected $description = 'Import B3 stocks from brapi and refresh their current price';

    public function handle(AssetSyncService $service): int
    {
        try {
            $summary = $service->sync();
        } catch (BrapiException $e) {
            $this->error('Asset sync failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        Cache::forget(Asset::SECTORS_CACHE_KEY);

        $this->info(sprintf(
            'Fetched %d, upserted %d, skipped %d.',
            $summary['fetched'],
            $summary['upserted'],
            $summary['skipped'],
        ));

        return self::SUCCESS;
    }
}
