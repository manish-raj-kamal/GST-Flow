<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleController extends Controller
{
    /**
     * Handle Google Sign-In credential (ID token) from the client-side button.
     * No client_secret needed — uses Google's tokeninfo endpoint to verify.
     */
    public function handleCredential(Request $request): JsonResponse
    {
        $request->validate([
            'credential' => ['required', 'string'],
        ]);

        try {
            // Decode the JWT payload (middle part) without verification first
            $parts = explode('.', $request->credential);
            if (count($parts) !== 3) {
                return response()->json(['success' => false, 'message' => 'Invalid token format.'], 400);
            }

            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

            if (! $payload || ! isset($payload['sub'], $payload['email'])) {
                return response()->json(['success' => false, 'message' => 'Invalid token payload.'], 400);
            }

            // Verify the token with Google's tokeninfo endpoint
            $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $request->credential,
            ]);

            if ($response->failed()) {
                return response()->json(['success' => false, 'message' => 'Token verification failed.'], 401);
            }

            $tokenInfo = $response->json();
            $expectedClientId = config('services.google.client_id');

            // Verify the audience matches our client ID
            if ($tokenInfo['aud'] !== $expectedClientId) {
                return response()->json(['success' => false, 'message' => 'Token audience mismatch.'], 401);
            }

            $googleId = $tokenInfo['sub'];
            $email = strtolower($tokenInfo['email']);
            $name = $tokenInfo['name'] ?? Str::before($email, '@');
            $avatar = $tokenInfo['picture'] ?? null;

            // Find or create user
            $user = User::where('google_id', $googleId)->first()
                ?: User::where('email', $email)->first();

            if ($user) {
                if (! $user->google_id) {
                    $user->update(['google_id' => $googleId, 'avatar' => $avatar]);
                }
            } else {
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'google_id' => $googleId,
                    'avatar' => $avatar,
                    'email_verified_at' => now(),
                    'password' => bcrypt(Str::random(32)),
                    'role' => 'business_user',
                    'is_active' => true,
                ]);
            }

            if (! $user->is_active) {
                return response()->json(['success' => false, 'message' => 'Account deactivated.'], 403);
            }

            Auth::login($user, remember: true);

            return response()->json([
                'success' => true,
                'redirect' => '/dashboard',
            ]);

        } catch (\Exception $e) {
            Log::error('Google Sign-In error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Authentication failed.'], 500);
        }
    }
}
