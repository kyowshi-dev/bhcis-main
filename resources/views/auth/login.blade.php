@extends('auth.layout')

@section('title', 'Login')
@section('heading', 'Sign in')
@section('helper', 'Pag-log in aron maka-access sa mga record sa pasyente ug mga serbisyo.')

@section('content')
<form action="{{ route('login.process') }}" method="POST" id="login-form">
    <input type="hidden" name="_token" id="csrf-token-input" value="{{ csrf_token() }}" autocomplete="off">

    @if (session('error') || session('session_expired') || request()->has('session_expired'))
        <div class="mb-4 p-3 text-sm border-l-4 bg-danger-soft" style="border-left-color: var(--danger); color: var(--danger);" data-auto-dismiss data-auto-dismiss-delay="10000">
            <p class="font-medium text-sm">{{ session('error', 'Your session has expired. Please sign in again.') }}</p>
        </div>
    @endif

    @if (session('success'))
        <div class="mb-4 p-3 text-sm border-l-4 bg-teal-soft" style="border-left-color: var(--primary); color: var(--primary);" data-auto-dismiss data-auto-dismiss-delay="10000">
            <p class="font-medium text-sm">{{ session('success') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 p-3 text-sm border-l-4 bg-danger-soft" style="border-left-color: var(--danger); color: var(--danger);" data-auto-dismiss data-auto-dismiss-delay="10000">
            @if (session('locked_until'))
                <p class="font-medium text-sm">
                    Login failed. This account is temporarily locked due to too many failed login attempts.
                    Try again in <span id="lock-countdown" data-locked-until="{{ (int) session('locked_until') }}">--:--</span>.
                </p>
            @else
                <p class="font-medium text-sm">Login failed. {{ $errors->first() }}</p>
            @endif
        </div>
    @endif

    <div class="mb-4">
        <label for="username" class="block text-sm font-medium mb-2.5" style="color: var(--ink);">Username</label>
        <input type="text" name="username" id="username" value="{{ old('username') }}"
               class="auth-input" placeholder="Username" required autocomplete="username">
    </div>

    <div class="mb-5">
        <label for="password" class="block text-sm font-medium mb-2.5" style="color: var(--ink);">Password</label>
        <div class="pw-wrap">
            <input type="password" name="password" id="password"
                   class="auth-input" placeholder="Password" required autocomplete="current-password">
            <button type="button" class="pw-toggle" aria-controls="password" aria-label="Show password">
                <i class="fa-solid fa-eye" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <div class="flex items-center justify-between mb-5 text-sm">
        <label class="flex items-center gap-3 text-sm cursor-pointer" style="color: var(--ink-muted);">
            <input type="checkbox" name="remember" class="h-4 w-4 rounded" style="accent-color: var(--primary);">
            Remember me
        </label>
        <a href="{{ route('password.forgot') }}" class="text-sm font-medium" style="color: var(--primary); text-decoration: underline;">
            Forgot Password?
        </a>
    </div>

    <button type="submit" class="auth-btn">Sign in</button>
</form>

<script>
    (function () {
        var username = document.getElementById('username');
        var password = document.getElementById('password');
        var hasErrors = {{ $errors->any() ? 'true' : 'false' }};

        if (hasErrors && password) {
            password.focus();
        } else if (username) {
            username.focus();
        }

        var countdown = document.getElementById('lock-countdown');

        if (countdown) {
            var lockedUntil = parseInt(countdown.getAttribute('data-locked-until'), 10) * 1000;

            function pad(n) {
                return String(n).padStart(2, '0');
            }

            function render() {
                var diff = Math.max(0, lockedUntil - Date.now());
                var minutes = Math.floor(diff / 60000);
                var seconds = Math.floor((diff % 60000) / 1000);
                countdown.textContent = pad(minutes) + ':' + pad(seconds);
            }

            render();
            setInterval(render, 1000);
        }
    })();
</script>
@endsection
