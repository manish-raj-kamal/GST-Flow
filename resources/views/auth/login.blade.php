<x-guest-layout :colorScheme="'indigo'" :pageTitle="'Sign In — ' . config('app.name')">

    <h2 style="font-size: 24px; font-weight: 700; color: #fff; margin-bottom: 4px;">Welcome back</h2>
    <p style="font-size: 14px; color: rgba(255,255,255,0.45); margin-bottom: 28px;">Sign in to your GST dashboard</p>

    {{-- Google Sign-In (client-side, no client_secret needed) --}}
    <div id="g_id_onload"
         data-client_id="{{ config('services.google.client_id') }}"
         data-callback="handleGoogleCredential"
         data-auto_prompt="false">
    </div>
    <div class="g_id_signin"
         data-type="standard"
         data-shape="rectangular"
         data-theme="filled_black"
         data-text="continue_with"
         data-size="large"
         data-width="360"
         style="margin-bottom: 12px; display: flex; justify-content: center;">
    </div>

    <div class="auth-divider">or sign in with email</div>

    {{-- Session Status --}}
    @if (session('status'))
        <div style="padding: 12px; border-radius: 10px; background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.2); color: #6ee7b7; font-size: 13px; margin-bottom: 16px;">
            {{ session('status') }}
        </div>
    @endif

    {{-- Tabs: Password / OTP --}}
    <div x-data="{ mode: 'password', otpSent: false, otpLoading: false, timer: 0 }">

        <div style="display: flex; gap: 4px; padding: 3px; border-radius: 10px; background: rgba(255,255,255,0.06); margin-bottom: 20px;">
            <button type="button" @click="mode = 'password'" :style="mode === 'password' ? 'background: rgba(255,255,255,0.12); color: #fff;' : 'background: transparent; color: rgba(255,255,255,0.4);'" style="flex: 1; padding: 8px; border-radius: 8px; border: none; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 150ms;">Password</button>
            <button type="button" @click="mode = 'otp'" :style="mode === 'otp' ? 'background: rgba(255,255,255,0.12); color: #fff;' : 'background: transparent; color: rgba(255,255,255,0.4);'" style="flex: 1; padding: 8px; border-radius: 8px; border: none; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 150ms;">Email OTP</button>
        </div>

        {{-- Password Login --}}
        <form method="POST" action="{{ route('login') }}" x-show="mode === 'password'" x-transition>
            @csrf
            <div style="margin-bottom: 16px;">
                <label class="auth-label">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="you@company.com">
                @error('email') <div class="auth-error">{{ $message }}</div> @enderror
            </div>
            <div style="margin-bottom: 16px;">
                <label class="auth-label">Password</label>
                <input type="password" name="password" required autocomplete="current-password" placeholder="••••••••">
                @error('password') <div class="auth-error">{{ $message }}</div> @enderror
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: rgba(255,255,255,0.45); cursor: pointer;">
                    <input type="checkbox" name="remember" style="accent-color: #818cf8;"> Remember me
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="auth-link">Forgot password?</a>
                @endif
            </div>
            <button type="submit" class="auth-btn">Sign In</button>
        </form>

        {{-- OTP Login --}}
        <form method="POST" action="{{ url('/auth/otp/verify') }}" x-show="mode === 'otp'" x-transition>
            @csrf
            <div style="margin-bottom: 16px;">
                <label class="auth-label">Email</label>
                <input type="email" name="email" x-ref="otpEmail" required placeholder="you@company.com" value="{{ old('email') }}">
                @error('email') <div class="auth-error">{{ $message }}</div> @enderror
            </div>

            <template x-if="!otpSent">
                <button type="button" class="auth-btn" :disabled="otpLoading" @click="
                    otpLoading = true;
                    fetch('/auth/otp/send', {
                        method: 'POST',
                        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                        body: JSON.stringify({email: $refs.otpEmail.value})
                    }).then(r => r.json()).then(d => {
                        if(d.success) { otpSent = true; timer = {{ config('app.otp_resend_cooldown', 45) }}; let iv = setInterval(()=>{timer--; if(timer<=0) clearInterval(iv);}, 1000); }
                        else { alert(d.message || 'Error sending OTP'); }
                        otpLoading = false;
                    }).catch(()=>{ otpLoading = false; alert('Network error'); })
                ">
                    <span x-show="!otpLoading">Send OTP</span>
                    <span x-show="otpLoading">Sending…</span>
                </button>
            </template>

            <template x-if="otpSent">
                <div>
                    <div style="margin-bottom: 16px;">
                        <label class="auth-label">Enter OTP</label>
                        <input type="text" name="otp" maxlength="6" placeholder="6-digit code" style="letter-spacing: 0.3em; text-align: center; font-size: 20px; font-weight: 600;" required>
                        @error('otp') <div class="auth-error">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="auth-btn" style="margin-bottom: 12px;">Verify & Sign In</button>
                    <div style="text-align: center;">
                        <button type="button" class="auth-link" style="border: none; background: none; cursor: pointer;" :disabled="timer > 0" @click="
                            fetch('/auth/otp/send', {
                                method: 'POST',
                                headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                                body: JSON.stringify({email: $refs.otpEmail.value})
                            }).then(r => r.json()).then(d => {
                                if(d.success) { timer = {{ config('app.otp_resend_cooldown', 45) }}; let iv = setInterval(()=>{timer--; if(timer<=0) clearInterval(iv);}, 1000); }
                            });
                        " x-text="timer > 0 ? 'Resend in ' + timer + 's' : 'Resend OTP'"></button>
                    </div>
                </div>
            </template>
        </form>
    </div>

    <div style="text-align: center; margin-top: 24px;">
        <span style="font-size: 13px; color: rgba(255,255,255,0.35);">Don't have an account?</span>
        <a href="{{ route('register') }}" class="auth-link" style="margin-left: 4px;">Create one</a>
    </div>

    {{-- Google Sign-In Script --}}
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script>
        function handleGoogleCredential(response) {
            fetch('/auth/google/credential', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ credential: response.credential }),
            })
            .then(r => r.json())
            .then(d => {
                if (d.success && d.redirect) {
                    window.location.href = d.redirect;
                } else {
                    alert(d.message || 'Google sign-in failed.');
                }
            })
            .catch(() => alert('Network error during Google sign-in.'));
        }
    </script>

</x-guest-layout>
