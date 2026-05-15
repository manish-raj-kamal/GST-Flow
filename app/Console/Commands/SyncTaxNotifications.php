<?php

namespace App\Console\Commands;

use App\Services\ActivityLogService;
use Illuminate\Console\Command;

class SyncTaxNotifications extends Command
{
    protected $signature = 'gst:sync-tax-notifications';

    protected $description = 'Run scheduled GST notification synchronization hook.';

    public function handle(ActivityLogService $activityLogService): int
    {
        $activityLogService->log('system', 'gst_tax_notifications_sync_triggered', [
            'triggered_at' => now()->toDateTimeString(),
        ]);

        $this->info('GST tax notification sync hook completed.');

        return self::SUCCESS;
    }
}

