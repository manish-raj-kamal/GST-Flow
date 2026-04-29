<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BusinessProfile;
use App\Models\Invoice;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function users(): JsonResponse
    {
        $actor = request()->user();
        $users = User::query()
            ->when($actor?->isTestAdmin(), fn ($query) => $query->whereIn('email', User::TEST_ACCOUNT_EMAILS))
            ->get()
            ->sortBy('name')
            ->values();

        return response()->json([
            'data' => $users,
        ]);
    }

    public function toggleUserStatus(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();

        if (
            $actor
            && (string) $actor->getKey() === (string) $user->getKey()
            && in_array($actor->role, ['admin', 'superadmin'], true)
        ) {
            return response()->json([
                'message' => 'You cannot deactivate your own admin account.',
            ], 422);
        }

        if ($actor?->isTestAdmin() && ! $user->isTestAccount()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        return response()->json([
            'message' => 'User status updated successfully.',
            'data' => $user->fresh(),
        ]);
    }

    public function assignRole(Request $request, User $user): JsonResponse
    {
        if ($request->user()?->isTestAdmin() && ! $user->isTestAccount()) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $validated = $request->validate([
            'role' => ['required', 'in:admin,business_user'],
        ]);

        $user->update([
            'role' => $validated['role'],
        ]);

        return response()->json([
            'message' => 'User role updated successfully.',
            'data' => $user->fresh(),
        ]);
    }

    public function analytics(): JsonResponse
    {
        return response()->json([
            'data' => [
                'users' => User::query()->count(),
                'active_users' => User::query()->where('is_active', true)->count(),
                'business_profiles' => BusinessProfile::query()->count(),
                'invoices' => Invoice::query()->count(),
                'reports' => Report::query()->count(),
                'recent_activity' => ActivityLog::query()->latest()->take(10)->get()->values(),
            ],
        ]);
    }
}
