<x-guest-layout :colorScheme="'indigo'" :pageTitle="'Sign In — ' . config('app.name')">

    <div class="auth-header">
        <p class="auth-kicker">Account access</p>
        <h2 class="auth-title">Welcome back</h2>
        <p class="auth-subtitle">Sign in to continue to your dashboard.</p>
    </div>

    {{-- Google Sign-In (client-side, no client_secret needed) --}}
    <div id="g_id_onload"
         data-client_id="{{ config('services.google.client_id') }}"
         data-callback="handleGoogleCredential"
         data-auto_prompt="false">
    </div>
    <div class="auth-google-wrap">
        <div class="g_id_signin"
             data-type="standard"
             data-shape="rectangular"
             data-theme="outline"
             data-text="continue_with"
             data-size="large">
        </div>
    </div>

    <div class="auth-divider"><span>or use email</span></div>

    {{-- Session Status --}}
    @if (session('status'))
        <div class="auth-status">
            {{ session('status') }}
        </div>
    @endif

    {{-- Tabs: Password / OTP --}}
    <div x-data="{
        mode: 'password',
        otpSent: false,
        otpLoading: false,
        timer: 0,
        testLogin(email, password) {
            this.mode = 'password';
            this.$nextTick(() => {
                this.$refs.passwordEmail.value = email;
                this.$refs.passwordInput.value = password;
                if (this.$refs.passwordForm.requestSubmit) {
                    this.$refs.passwordForm.requestSubmit();
                    return;
                }

                this.$refs.passwordForm.submit();
            });
        },
    }" class="auth-flow">

        <div class="auth-tabs" role="tablist" aria-label="Sign in method">
            <button type="button"
                class="auth-tab"
                :class="{ 'is-active': mode === 'password' }"
                :aria-selected="(mode === 'password').toString()"
                @click="mode = 'password'">
                Password
            </button>
            <button type="button"
                class="auth-tab"
                :class="{ 'is-active': mode === 'otp' }"
                :aria-selected="(mode === 'otp').toString()"
                @click="mode = 'otp'">
                Email OTP
            </button>
        </div>

        {{-- Password Login --}}
        <form method="POST" action="{{ route('login') }}" x-show="mode === 'password'" x-transition class="auth-form" x-ref="passwordForm">
            @csrf
            <div class="auth-field">
                <label class="auth-label" for="login_email">Email</label>
                <input id="login_email" type="email" name="email" x-ref="passwordEmail" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="you@company.com">
                @error('email') <div class="auth-error">{{ $message }}</div> @enderror
            </div>

            <div class="auth-field">
                <label class="auth-label" for="login_password">Password</label>
                <input id="login_password" type="password" name="password" x-ref="passwordInput" required autocomplete="current-password" placeholder="Enter your password">
                @error('password') <div class="auth-error">{{ $message }}</div> @enderror
            </div>

            <div class="auth-row">
                <label class="auth-remember">
                    <input type="checkbox" name="remember">
                    <span>Remember me</span>
                </label>

                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="auth-link">Forgot password?</a>
                @endif
            </div>

            <button type="submit" class="auth-btn">Sign in</button>
        </form>

        {{-- OTP Login --}}
        <form method="POST" action="{{ url('/auth/otp/verify') }}" x-show="mode === 'otp'" x-transition class="auth-form">
            @csrf
            <div class="auth-field">
                <label class="auth-label" for="login_otp_email">Email</label>
                <input id="login_otp_email" type="email" name="email" x-ref="otpEmail" required placeholder="you@company.com" value="{{ old('email') }}">
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
                    <span x-show="otpLoading">Sending...</span>
                </button>
            </template>

            <template x-if="otpSent">
                <div class="auth-form">
                    <div class="auth-field">
                        <label class="auth-label" for="login_otp_code">Enter OTP</label>
                        <input id="login_otp_code" class="auth-code-input" type="text" name="otp" maxlength="6" inputmode="numeric" placeholder="000000" required>
                        @error('otp') <div class="auth-error">{{ $message }}</div> @enderror
                    </div>

                    <button type="submit" class="auth-btn">Verify and sign in</button>

                    <div class="auth-resend">
                        <button type="button" class="auth-text-button" :disabled="timer > 0" @click="
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

        <div class="auth-switch">
            <span>Don't have an account?</span>
            <a href="{{ route('register') }}" class="auth-link">Create one</a>
        </div>

        <div class="auth-test-logins">
            <span class="auth-test-title">Test login</span>

            <div class="auth-test-grid">
                <button type="button" class="auth-test-button" @click="testLogin('admin@gstplatform.com', 'admin123')">
                    <span class="auth-test-avatar">AD</span>
                    <span>Admin</span>
                </button>

                <button type="button" class="auth-test-button" @click="testLogin('demo@gstplatform.com', 'demo123')">
                    <span class="auth-test-avatar">BU</span>
                    <span>User</span>
                </button>

                <button type="button" class="auth-test-button" @click="testLogin('manager@gstplatform.com', 'manager123')">
                    <span class="auth-test-avatar">MG</span>
                    <span>Manager</span>
                </button>
            </div>
        </div>
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
