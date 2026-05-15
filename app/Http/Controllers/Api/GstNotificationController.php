<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GstChangeNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GstNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_if(! $request->user()->isAdmin(), 403, 'Forbidden');

        $notifications = GstChangeNotification::query()
            ->orderByDesc('created_at')
            ->limit(200)
            ->get()
            ->values();

        return response()->json(['data' => $notifications]);
    }

    public function markRead(Request $request, GstChangeNotification $notification): JsonResponse
    {
        abort_if(! $request->user()->isAdmin(), 403, 'Forbidden');

        $notification->update(['is_read' => true]);

        return response()->json(['message' => 'Notification marked as read.', 'data' => $notification->fresh()]);
    }
}

