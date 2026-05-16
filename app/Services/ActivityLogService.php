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
            'ip_address' => $this->resolvePreferredIp($request),
            'user_agent' => $request?->userAgent(),
            'meta' => $meta,
        ]);
    }

    private function resolvePreferredIp(?Request $request): ?string
    {
        if (! $request) {
            return null;
        }

        $ips = collect($request->ips())
            ->filter(fn (?string $ip): bool => filled($ip) && filter_var($ip, FILTER_VALIDATE_IP) !== false)
            ->values();

        return $ips->first(fn (string $ip): bool => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false)
            ?? $ips->first(fn (string $ip): bool => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false)
            ?? $request->ip();
    }
}
