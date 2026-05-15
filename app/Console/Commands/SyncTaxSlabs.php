<?php

namespace App\Console\Commands;

use App\Services\ActivityLogService;
use Illuminate\Console\Command;

class SyncTaxSlabs extends Command
{
    protected $signature = 'gst:sync-tax-slabs';

    protected $description = 'Run scheduled tax slab synchronization hook.';

    public function handle(ActivityLogService $activityLogService): int
    {
        $activityLogService->log('system', 'gst_tax_slab_sync_triggered', [
            'triggered_at' => now()->toDateTimeString(),
        ]);

        $this->info('GST tax slab sync hook completed.');

        return self::SUCCESS;
    }
}

