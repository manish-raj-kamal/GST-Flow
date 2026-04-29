<x-guest-layout :colorScheme="'emerald'" :pageTitle="'Create Account — ' . config('app.name')">

    <div class="auth-header">
        <p class="auth-kicker">New workspace</p>
        <h2 class="auth-title">Create your account</h2>
        <p class="auth-subtitle">Create a password, then verify your email with an OTP.</p>
    </div>

    {{-- Google Sign-Up (client-side, no client_secret needed) --}}
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
             data-text="signup_with"
             data-size="large"
             data-width="360">
        </div>
    </div>

    <div class="auth-divider"><span>or use email</span></div>

    <div x-data="{ otpSent: {{ $errors->has('otp') ? 'true' : 'false' }}, otpLoading: false, timer: 0 }" class="auth-flow">
        <form method="POST" action="{{ route('register') }}" class="auth-form" x-ref="registerForm">
            @csrf

            <div class="auth-field">
                <label class="auth-label" for="register_name">Full name</label>
                <input id="register_name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" placeholder="John Doe">
                @error('name') <div class="auth-error">{{ $message }}</div> @enderror
            </div>

            <div class="auth-field">
                <label class="auth-label" for="register_email">Email</label>
                <input id="register_email" type="email" name="email" x-ref="email" value="{{ old('email') }}" required autocomplete="username" placeholder="you@company.com">
                @error('email') <div class="auth-error">{{ $message }}</div> @enderror
            </div>

            <div class="auth-field">
                <label class="auth-label" for="register_password">Password</label>
                <input id="register_password" type="password" name="password" x-ref="password" required minlength="8" autocomplete="new-password" placeholder="Minimum 8 characters">
                @error('password') <div class="auth-error">{{ $message }}</div> @enderror
            </div>

            <div class="auth-field">
                <label class="auth-label" for="register_password_confirmation">Confirm password</label>
                <input id="register_password_confirmation" type="password" name="password_confirmation" x-ref="passwordConfirmation" required minlength="8" autocomplete="new-password" placeholder="Repeat password">
            </div>

            <template x-if="otpSent">
                <div class="auth-field">
                    <label class="auth-label" for="register_otp">Email OTP</label>
                    <input id="register_otp" class="auth-code-input" type="text" name="otp" maxlength="6" inputmode="numeric" placeholder="000000" required>
                    @error('otp') <div class="auth-error">{{ $message }}</div> @enderror
                </div>
            </template>

            <template x-if="!otpSent">
                <button type="button" class="auth-btn" :disabled="otpLoading" @click="
                    $refs.passwordConfirmation.setCustomValidity('');

                    if ($refs.password.value !== $refs.passwordConfirmation.value) {
                        $refs.passwordConfirmation.setCustomValidity('Passwords do not match.');
                    }

                    if (!$refs.registerForm.reportValidity()) {
                        return;
                    }

                    otpLoading = true;
                    fetch('/auth/otp/send', {
                        method: 'POST',
                        headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                        body: JSON.stringify({email: $refs.email.value, purpose: 'register'})
                    }).then(r => r.json()).then(d => {
                        if(d.success) {
                            otpSent = true;
                            timer = {{ config('app.otp_resend_cooldown', 45) }};
                            let iv = setInterval(()=>{timer--; if(timer<=0) clearInterval(iv);}, 1000);
                        } else {
                            alert(d.message || 'Error sending OTP');
                        }
                        otpLoading = false;
                    }).catch(()=>{ otpLoading = false; alert('Network error'); })
                ">
                    <span x-show="!otpLoading">Send verification OTP</span>
                    <span x-show="otpLoading">Sending...</span>
                </button>
            </template>

            <template x-if="otpSent">
                <div class="auth-form">
                    <button type="submit" class="auth-btn">Verify and create account</button>

                    <div class="auth-resend">
                        <button type="button" class="auth-text-button" :disabled="timer > 0" @click="
                            fetch('/auth/otp/send', {
                                method: 'POST',
                                headers: {'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'},
                                body: JSON.stringify({email: $refs.email.value, purpose: 'register'})
                            }).then(r => r.json()).then(d => {
                                if(d.success) {
                                    timer = {{ config('app.otp_resend_cooldown', 45) }};
                                    let iv = setInterval(()=>{timer--; if(timer<=0) clearInterval(iv);}, 1000);
                                } else {
                                    alert(d.message || 'Error sending OTP');
                                }
                            });
                        " x-text="timer > 0 ? 'Resend in ' + timer + 's' : 'Resend OTP'"></button>
                    </div>
                </div>
            </template>
        </form>
    </div>

    <div class="auth-switch">
        <span>Already have an account?</span>
        <a href="{{ route('login') }}" class="auth-link">Sign in</a>
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
