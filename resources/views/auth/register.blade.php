<x-guest-layout :colorScheme="'emerald'" :pageTitle="'Create Account — ' . config('app.name')">

    <h2 style="font-size: 24px; font-weight: 700; color: #fff; margin-bottom: 4px;">Create your account</h2>
    <p style="font-size: 14px; color: rgba(255,255,255,0.45); margin-bottom: 28px;">Start managing GST compliance for free</p>

    {{-- Google Sign-Up (client-side, no client_secret needed) --}}
    <div id="g_id_onload"
         data-client_id="{{ config('services.google.client_id') }}"
         data-callback="handleGoogleCredential"
         data-auto_prompt="false">
    </div>
    <div class="g_id_signin"
         data-type="standard"
         data-shape="rectangular"
         data-theme="filled_black"
         data-text="signup_with"
         data-size="large"
         data-width="360"
         style="margin-bottom: 12px; display: flex; justify-content: center;">
    </div>

    <div class="auth-divider">or register with email</div>

    {{-- Tabs: Password / OTP --}}
    <div x-data="{ mode: 'password', otpSent: false, otpLoading: false, timer: 0 }">

        <div style="display: flex; gap: 4px; padding: 3px; border-radius: 10px; background: rgba(255,255,255,0.06); margin-bottom: 20px;">
            <button type="button" @click="mode = 'password'" :style="mode === 'password' ? 'background: rgba(255,255,255,0.12); color: #fff;' : 'background: transparent; color: rgba(255,255,255,0.4);'" style="flex: 1; padding: 8px; border-radius: 8px; border: none; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 150ms;">With Password</button>
            <button type="button" @click="mode = 'otp'" :style="mode === 'otp' ? 'background: rgba(255,255,255,0.12); color: #fff;' : 'background: transparent; color: rgba(255,255,255,0.4);'" style="flex: 1; padding: 8px; border-radius: 8px; border: none; font-size: 13px; font-weight: 500; cursor: pointer; transition: all 150ms;">With OTP</button>
        </div>

        {{-- Password Registration --}}
        <form method="POST" action="{{ route('register') }}" x-show="mode === 'password'" x-transition>
            @csrf
            <div style="margin-bottom: 14px;">
                <label class="auth-label">Full Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="John Doe">
                @error('name') <div class="auth-error">{{ $message }}</div> @enderror
            </div>
            <div style="margin-bottom: 14px;">
                <label class="auth-label">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="you@company.com">
                @error('email') <div class="auth-error">{{ $message }}</div> @enderror
            </div>
            <div style="margin-bottom: 14px;">
                <label class="auth-label">Password</label>
                <input type="password" name="password" required autocomplete="new-password" placeholder="Min. 8 characters">
                @error('password') <div class="auth-error">{{ $message }}</div> @enderror
            </div>
            <div style="margin-bottom: 20px;">
                <label class="auth-label">Confirm Password</label>
                <input type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Repeat password">
            </div>
            <button type="submit" class="auth-btn">Create Account</button>
        </form>

        {{-- OTP Registration --}}
        <form method="POST" action="{{ url('/auth/otp/verify') }}" x-show="mode === 'otp'" x-transition>
            @csrf
            <input type="hidden" name="register" value="1">
            <div style="margin-bottom: 14px;">
                <label class="auth-label">Full Name</label>
                <input type="text" name="name" required placeholder="John Doe" value="{{ old('name') }}">
            </div>
            <div style="margin-bottom: 14px;">
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
                    <button type="submit" class="auth-btn" style="margin-bottom: 12px;">Verify & Create Account</button>
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
        <span style="font-size: 13px; color: rgba(255,255,255,0.35);">Already have an account?</span>
        <a href="{{ route('login') }}" class="auth-link" style="margin-left: 4px;">Sign in</a>
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
