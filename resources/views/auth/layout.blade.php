<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'BHCIS') - Sta. Ana</title>
    <script>
        (function () {
            try {
                var stored = localStorage.getItem('bhcis-theme');
                var dark = stored === 'dark' || (stored !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
                if (dark) document.documentElement.classList.add('dark');
            } catch (e) {}
        })();
    </script>
    <link rel="icon" type="image/svg+xml" href="{{ asset('img/logo.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .auth-body {
            min-height: 100vh;
            font-family: var(--font-sans, Poppins, sans-serif);
            color: var(--ink);
            background: var(--bg-page);
        }

        .auth-shell {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* --- Brand panel: compact strip on mobile, full panel from lg --- */

        .auth-brand {
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            padding: 1rem 1.25rem;
            color: var(--on-primary);
            background: linear-gradient(165deg, var(--primary-light) 0%, var(--primary) 50%, var(--primary-hover) 100%);
        }

        .auth-brand::before {
            content: "";
            position: absolute;
            inset: 0;
            background: url('{{ asset('img/bg.svg') }}') center/cover no-repeat;
            opacity: 0.08;
            pointer-events: none;
        }

        .auth-brand-inner {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 2rem;
            width: 100%;
        }

        .auth-brand-top {
            display: flex;
            align-items: center;
            gap: 0.85rem;
        }

        .auth-brand-logo {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            flex-shrink: 0;
        }

        .auth-brand-name {
            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1.3;
        }

        .auth-brand-facility {
            font-size: 0.78rem;
            opacity: 0.85;
            margin-top: 0.15rem;
        }

        .auth-brand-foot {
            display: none;
            font-size: 0.78rem;
            line-height: 1.6;
            opacity: 0.9;
        }

        .auth-brand-foot a {
            color: var(--on-primary);
            font-weight: 600;
        }

        .auth-brand-foot a:hover {
            text-decoration: underline;
        }

        /* --- Form panel --- */

        .auth-panel {
            position: relative;
            overflow: hidden;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: clamp(1.25rem, 4vw, 3rem) clamp(1rem, 4vw, 2.5rem);
        }

        /* Decorative polka dots - static, token-colored, aria-hidden */
        .auth-deco {
            position: absolute;
            inset: 0;
            pointer-events: none;
            z-index: 0;
        }

        .auth-deco svg {
            position: absolute;
            display: block;
            overflow: visible;
        }

        .auth-deco-tr {
            top: 1.5rem;
            right: 1.5rem;
        }

        .auth-deco-bl {
            bottom: 1.5rem;
            left: 1.5rem;
        }

        .auth-card {
            position: relative;
            z-index: 1;
            width: min(100%, 26rem);
            background: var(--bg-surface-elevated);
            border: 1px solid var(--border);
            border-radius: 1.25rem;
            box-shadow: var(--shadow-lg);
            padding: clamp(1.5rem, 4vw, 2.25rem);
        }

        /* Brand-panel polka dots - desktop only */
        .auth-brand-deco {
            display: none;
            position: absolute;
            inset: 0;
            pointer-events: none;
        }

        .auth-brand-deco svg {
            position: absolute;
            display: block;
            overflow: visible;
        }

        .auth-brand-deco-tr {
            top: 1.5rem;
            right: 1.5rem;
        }

        .auth-brand-deco-br {
            bottom: 1.5rem;
            right: 1.5rem;
        }

        .auth-heading {
            font-size: clamp(1.35rem, 3vw, 1.6rem);
            font-weight: 700;
            color: var(--ink);
            line-height: 1.25;
        }

        .auth-helper {
            font-size: 0.82rem;
            color: var(--ink-muted);
            line-height: 1.55;
            margin-top: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .auth-card-foot {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-top: 1.5rem;
            padding-top: 1.25rem;
            border-top: 1px solid var(--border);
            text-align: center;
            font-size: 0.75rem;
            line-height: 1.5;
            color: var(--ink-muted);
        }

        .auth-card-foot a {
            color: var(--primary);
            font-weight: 600;
        }

        .auth-card-foot a:hover {
            text-decoration: underline;
        }

        .auth-links-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .auth-brand-inline-meta {
            display: none;
        }

        /* --- Fields / button --- */

        .auth-input {
            width: 100%;
            min-height: 3rem;
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            padding: 0.8rem 1rem;
            font-size: 0.875rem;
            color: var(--ink);
            background: var(--bg-surface-elevated);
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .auth-input::placeholder {
            color: var(--ink-muted);
        }

        .auth-input:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px var(--ring);
        }

        .auth-btn {
            width: 100%;
            min-height: 3rem;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--on-primary);
            background: var(--primary);
            box-shadow: 0 4px 14px -2px rgba(13, 74, 60, 0.5);
            transition: opacity 0.2s ease, transform 0.1s ease, box-shadow 0.2s ease;
        }

        .auth-btn:hover {
            opacity: 0.95;
            box-shadow: 0 6px 18px -2px rgba(13, 74, 60, 0.55);
        }

        .auth-btn:active {
            transform: scale(0.99);
        }

        .auth-btn:disabled {
            background: var(--ink-subtle);
            box-shadow: none;
            cursor: not-allowed;
            opacity: 1;
        }

        .auth-btn:disabled:hover {
            opacity: 1;
        }

        .muted-xs {
            font-size: 0.78rem;
            color: var(--ink-muted);
        }

        .otp-row {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }

        .otp-digit {
            width: clamp(2.75rem, 12.5vw, 3.25rem);
            height: clamp(3rem, 13.5vw, 3.5rem);
            text-align: center;
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--ink);
            background: var(--bg-surface-elevated);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .otp-digit:focus {
            outline: none;
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px var(--ring);
        }

        .otp-digit.is-invalid {
            border-color: var(--danger);
        }

        .otp-digit.is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.25);
        }

        .otp-hint {
            font-size: 0.75rem;
            color: var(--ink-muted);
        }

        .resend-btn {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--primary);
            text-decoration: underline;
            transition: opacity 0.15s ease;
        }

        .resend-btn:disabled {
            color: var(--ink-subtle);
            text-decoration: none;
            cursor: not-allowed;
        }

        .pw-wrap {
            position: relative;
        }

        .pw-toggle {
            position: absolute;
            top: 50%;
            right: 0.25rem;
            transform: translateY(-50%);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border: none;
            background: transparent;
            color: var(--ink-muted);
            cursor: pointer;
        }

        .pw-toggle:hover {
            color: var(--ink);
        }

        .pw-toggle:focus-visible {
            outline: none;
            border-radius: 0.5rem;
            box-shadow: 0 0 0 3px var(--ring);
        }

        .pw-wrap .auth-input {
            padding-right: 3.25rem;
        }

        .theme-toggle {
            position: absolute;
            top: 1rem;
            right: 1rem;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border: none;
            border-radius: 0.5rem;
            background: transparent;
            color: var(--ink-muted);
            cursor: pointer;
        }

        .theme-toggle:hover {
            color: var(--ink);
        }

        .theme-toggle:focus-visible {
            outline: none;
            box-shadow: 0 0 0 3px var(--ring);
        }

        .theme-toggle .icon-sun {
            display: none;
        }

        html.dark .theme-toggle .icon-sun {
            display: inline-block;
        }

        html.dark .theme-toggle .icon-moon {
            display: none;
        }

        /* --- Split-screen from lg --- */

        @media (min-width: 1024px) {
            .auth-shell {
                flex-direction: row;
            }

            .auth-brand {
                flex: 0 0 clamp(340px, 40%, 520px);
                align-items: stretch;
                padding: clamp(2rem, 4vw, 3.5rem);
            }

            .auth-brand-top {
                flex-direction: column;
                align-items: flex-start;
                gap: 1.5rem;
            }

            .auth-brand-logo {
                width: 64px;
                height: 64px;
                border-radius: 16px;
                box-shadow: var(--shadow-md);
            }

            .auth-brand-name {
                font-size: clamp(1.35rem, 1.8vw, 1.75rem);
                line-height: 1.25;
            }

            .auth-brand-facility {
                font-size: 0.9rem;
                margin-top: 0.35rem;
            }

            .auth-brand-foot {
                display: block;
            }

            .auth-brand-deco {
                display: block;
            }
        }

        @media (max-width: 1023.98px) {
            .auth-brand-inline-meta {
                display: block;
            }
        }
    </style>
</head>
<body class="auth-body antialiased">
    <div class="auth-shell">
        <aside class="auth-brand" aria-label="BHCIS Sta. Ana identity">
            <div class="auth-brand-deco" aria-hidden="true">
                <svg class="auth-brand-deco-tr" width="48" height="48" viewBox="0 0 48 48" focusable="false">
                    <circle cx="38" cy="10" r="7" fill="rgba(255,255,255,0.16)" />
                    <circle cx="18" cy="22" r="4" fill="rgba(255,255,255,0.11)" />
                </svg>
                <svg class="auth-brand-deco-br" width="48" height="48" viewBox="0 0 48 48" focusable="false">
                    <circle cx="10" cy="38" r="8" fill="rgba(255,255,255,0.14)" />
                    <circle cx="30" cy="26" r="4" fill="rgba(255,255,255,0.10)" />
                </svg>
            </div>
            <div class="auth-brand-inner">
                <div class="auth-brand-top">
                    <div class="logo-mark auth-brand-logo">
                        <img src="{{ asset('img/logo.svg') }}" alt="">
                    </div>
                    <div>
                        <p class="auth-brand-name">Barangay Health Center Information System</p>
                        <p class="auth-brand-facility">Sta. Ana Health Center</p>
                    </div>
                </div>
                <div class="auth-brand-foot">
                    @include('auth.partials._credit')
                </div>
            </div>
        </aside>

        <main class="auth-panel">
            <div class="auth-deco" aria-hidden="true">
                <svg class="auth-deco-tr" width="48" height="48" viewBox="0 0 48 48" focusable="false">
                    <circle cx="38" cy="10" r="7" style="fill: var(--primary)" opacity="0.14" />
                    <circle cx="18" cy="22" r="4" style="fill: var(--primary)" opacity="0.10" />
                </svg>
                <svg class="auth-deco-bl" width="48" height="48" viewBox="0 0 48 48" focusable="false">
                    <circle cx="10" cy="38" r="8" style="fill: var(--primary)" opacity="0.14" />
                    <circle cx="30" cy="26" r="4" style="fill: var(--primary)" opacity="0.10" />
                </svg>
            </div>
            <button type="button" id="theme-toggle" class="theme-toggle"
                    aria-pressed="false" aria-label="Switch to dark mode" title="Switch to dark mode">
                <i class="fa-solid fa-moon icon-moon text-lg" aria-hidden="true"></i>
                <i class="fa-solid fa-sun icon-sun text-lg" aria-hidden="true"></i>
            </button>
            <div class="auth-card animate-in opacity-0">
                <h1 class="auth-heading">@yield('heading', 'Sign in')</h1>
                @hasSection('helper')
                    <p class="auth-helper">@yield('helper')</p>
                @endif

                @yield('content')

                <div class="auth-card-foot">
                    <div class="auth-links-row">
                        <a href="{{ route('privacy.policy') }}">Privacy Policy</a>
                        <span aria-hidden="true">|</span>
                        <a href="{{ route('privacy.liability') }}">Liability &amp; Disclaimer</a>
                    </div>
                    <div class="auth-brand-inline-meta">
                        @include('auth.partials._credit')
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        (function () {
            var tokenMeta = document.querySelector('meta[name="csrf-token"]');
            var refreshIntervalMs = Math.max(60000, {{ (int) config('session.lifetime') * 60 * 1000 }} / 2);

            function tokenInput() {
                return document.getElementById('csrf-token-input')
                    || document.querySelector('input[name="_token"]');
            }

            function updateCsrfToken(token) {
                var input = tokenInput();
                if (input) {
                    input.value = token;
                }
                if (tokenMeta) {
                    tokenMeta.setAttribute('content', token);
                }
            }

            function refreshCsrfToken() {
                fetch('{{ route('login') }}', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (data.token) {
                        updateCsrfToken(data.token);
                    }
                })
                .catch(function () {
                    // Keep the last known token; server-side handler redirects on mismatch.
                });
            }

            window.addEventListener('pageshow', function (event) {
                if (event.persisted) {
                    window.location.reload();
                }
            });

            document.addEventListener('visibilitychange', function () {
                if (document.visibilityState === 'visible') {
                    refreshCsrfToken();
                }
            });

            setInterval(refreshCsrfToken, refreshIntervalMs);
        })();

        (function () {
            Array.prototype.slice.call(document.querySelectorAll('.pw-toggle')).forEach(function (toggle) {
                toggle.addEventListener('click', function () {
                    var input = document.getElementById(toggle.getAttribute('aria-controls'));
                    var show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    var icon = toggle.querySelector('i');
                    icon.classList.toggle('fa-eye', !show);
                    icon.classList.toggle('fa-eye-slash', show);
                    toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                });
            });
        })();

        (function () {
            var btn = document.getElementById('theme-toggle');
            if (!btn) return;

            function sync() {
                var dark = document.documentElement.classList.contains('dark');
                btn.setAttribute('aria-pressed', dark ? 'true' : 'false');
                var label = dark ? 'Switch to light mode' : 'Switch to dark mode';
                btn.setAttribute('aria-label', label);
                btn.setAttribute('title', label);
            }

            sync();
            btn.addEventListener('click', function () {
                var dark = !document.documentElement.classList.contains('dark');
                document.documentElement.classList.toggle('dark', dark);
                try {
                    localStorage.setItem('bhcis-theme', dark ? 'dark' : 'light');
                } catch (e) {}
                document.cookie = 'bhcis-theme=' + (dark ? 'dark' : 'light') + '; expires=Fri, 31 Dec 2099 23:59:59 GMT; path=/';
                sync();
            });
        })();
    </script>
</body>
</html>
