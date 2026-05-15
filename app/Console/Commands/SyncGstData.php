<?php

namespace App\Console\Commands;

use App\Services\HsnCatalogSyncService;
use Illuminate\Console\Command;

class SyncGstData extends Command
{
    protected $signature = 'gst:sync {--source= : Specific source URL to sync}';
    protected $description = 'Synchronize GST HSN codes and tax slabs from external sources';

    public function handle(HsnCatalogSyncService $syncService): int
    {
        $this->info('Starting GST Data Synchronization...');

        $sources = $this->option('source') 
            ? [$this->option('source')] 
            : config('services.gst.sync_sources', [
                'https://api.example.com/gst/hsn-codes', // Placeholder
            ]);

        $results = $syncService->sync($sources);

        $this->table(['Created', 'Updated', 'Failures'], [
            [$results['created'], $results['updated'], count($results['failed_sources'])]
        ]);

        if (!empty($results['failed_sources'])) {
            $this->error('Some sources failed to sync:');
            foreach ($results['failed_sources'] as $failure) {
                $this->warn("- {$failure['source']}: {$failure['error']}");
            }
        }

        $this->info('GST Data Synchronization complete.');
        
        // Audit rates after sync
        $this->info('Running Product Tax Audit...');
        $auditResults = $syncService->auditProductRates();
        if (count($auditResults) > 0) {
            $this->warn(count($auditResults) . ' products found with outdated GST rates!');
            $this->table(['Product', 'HSN', 'Stored Rate', 'Current Rate'], array_map(fn($r) => [
                $r['product_name'], $r['hsn_code'], $r['stored_rate'], $r['current_rate']
            ], $auditResults));
        } else {
            $this->info('All products are compliant with current GST rates.');
        }

        return 0;
    }
}
