<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogService
{
    public function log(User|string|null $user, string $actionType, array $affectedRecord = [], ?Request $request = null, array $meta = []): void
    {
        $userId = $user instanceof User ? $user->getKey() : $user;

        ActivityLog::create([
            'user_id' => $userId,
            'action_type' => $actionType,
            'affected_record' => $affectedRecord,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'meta' => $meta,
        ]);
    }
}
