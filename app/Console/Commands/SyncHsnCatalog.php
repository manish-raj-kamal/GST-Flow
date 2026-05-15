<?php

namespace App\Console\Commands;

use App\Services\HsnCatalogSyncService;
use Illuminate\Console\Command;

class SyncHsnCatalog extends Command
{
    protected $signature = 'hsn:sync {--source=* : Public JSON/CSV source URL(s)}';

    protected $description = 'Sync HSN catalog from public sources and ensure all product HSNs exist in master.';

    public function handle(HsnCatalogSyncService $syncService): int
    {
        $sources = (array) $this->option('source');

        if (empty($sources)) {
            $sources = config('services.hsn_catalog.sources', []);
        }

        $result = $syncService->sync($sources);

        $this->info("HSN sync complete. Created: {$result['created']}, Updated: {$result['updated']}");

        if (! empty($result['failed_sources'])) {
            $this->warn('Some sources failed:');
            foreach ($result['failed_sources'] as $failed) {
                $this->line("- {$failed['source']} => {$failed['error']}");
            }
        }

        return self::SUCCESS;
    }
}

