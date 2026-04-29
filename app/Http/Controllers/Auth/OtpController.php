<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class OtpController extends Controller
{
    /**
     * Send a 6-digit OTP to the given email address.
     */
    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'purpose' => ['nullable', 'string', 'in:login,register'],
        ]);

        $email = strtolower($request->email);
        $purpose = $request->input('purpose', 'login');
        $cooldownKey = "otp_cooldown:{$email}";
        $attemptsKey = "otp_attempts:{$email}";

        if ($purpose === 'register' && User::where('email', $email)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'An account already exists with this email.',
            ], 422);
        }

        // Rate limit: cooldown between sends
        if (Cache::has($cooldownKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Please wait before requesting another OTP.',
            ], 429);
        }

        // Max attempts per hour
        $maxAttempts = (int) config('app.otp_max_attempts', 5);
        $attempts = (int) Cache::get($attemptsKey, 0);
        if ($attempts >= $maxAttempts) {
            return response()->json([
                'success' => false,
                'message' => 'Too many attempts. Try again later.',
            ], 429);
        }

        // Generate 6-digit OTP
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiryMinutes = (int) config('app.otp_expiry_minutes', 10);
        $cooldownSeconds = (int) config('app.otp_resend_cooldown', 45);

        // Store OTP in cache
        Cache::put("otp:{$email}", $otp, now()->addMinutes($expiryMinutes));
        Cache::put($cooldownKey, true, now()->addSeconds($cooldownSeconds));
        Cache::put($attemptsKey, $attempts + 1, now()->addHour());

        // Send email
        try {
            Mail::send('emails.otp-code', [
                'otp' => $otp,
                'expiryMinutes' => $expiryMinutes,
                'appName' => config('app.name'),
            ], function ($message) use ($email) {
                $message->to($email)
                    ->subject('Your OTP Code — ' . config('app.name'));
            });
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email. Please try again.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your email.',
        ]);
    }

    /**
     * Verify the OTP and log the user in.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'otp' => ['required', 'string', 'size:6'],
        ]);

        $email = strtolower($request->email);
        $cachedOtp = Cache::get("otp:{$email}");

        if (! $cachedOtp || $cachedOtp !== $request->otp) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP.'])->withInput();
        }

        // OTP is valid — clear it
        Cache::forget("otp:{$email}");
        Cache::forget("otp_attempts:{$email}");

        $user = User::where('email', $email)->first();

        if (! $user) {
            return back()->withErrors(['email' => 'No account found with this email.'])->withInput();
        }

        if (! $user->is_active) {
            return back()->withErrors(['email' => 'Your account has been deactivated.'])->withInput();
        }

        // Mark email as verified
        if (! $user->email_verified_at) {
            $user->update(['email_verified_at' => now()]);
        }

        Auth::login($user, remember: true);

        return redirect()->intended('/dashboard');
    }
}
