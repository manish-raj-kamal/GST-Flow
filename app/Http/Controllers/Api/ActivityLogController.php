<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = ActivityLog::query()
            ->latest()
            ->get()
            ->filter(fn (ActivityLog $log) => $request->user()->isAdmin() || $log->user_id === $request->user()->id)
            ->filter(fn (ActivityLog $log) => blank($request->query('action_type')) || $log->action_type === $request->query('action_type'))
            ->take(100)
            ->values();

        return response()->json(['data' => $logs]);
    }
}
