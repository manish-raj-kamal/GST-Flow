<?php

namespace App\Console\Commands;

use App\Services\ActivityLogService;
use Illuminate\Console\Command;

class SyncComplianceUpdates extends Command
{
    protected $signature = 'gst:sync-compliance-updates';

    protected $description = 'Run scheduled compliance update synchronization hook.';

    public function handle(ActivityLogService $activityLogService): int
    {
        $activityLogService->log('system', 'gst_compliance_sync_triggered', [
            'triggered_at' => now()->toDateTimeString(),
        ]);

        $this->info('GST compliance sync hook completed.');

        return self::SUCCESS;
    }
}

