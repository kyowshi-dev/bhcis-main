<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        .theme-toggle .icon-sun {
            display: none;
        }

        html.dark .theme-toggle .icon-sun {
            display: inline-block;
        }

        html.dark .theme-toggle .icon-moon {
            display: none;
        }
    </style>
</head>

<body class="min-h-screen font-sans text-ink antialiased" style="background: var(--bg-page);">
    <div class="min-h-screen flex flex-col">

        <main class="flex-1">
            <div class="max-w-3xl mx-auto px-4 py-8">
                @yield('content')
            </div>
        </main>

        <footer class="shrink-0 border-t border-border z-10" style="background: var(--bg-surface); color: var(--ink-subtle);">
            <div class="max-w-3xl mx-auto px-4 py-4 text-center text-xs" style="color: var(--ink-muted);">
                &copy; {{ date('Y') }} Barangay Sta. Ana Health Center. All rights reserved.
                <span class="mx-1">|</span>
                <a href="{{ route('privacy.policy') }}" class="hover:underline" style="color: var(--ink-muted);">Privacy Policy</a>
                <span class="mx-1">|</span>
                <a href="{{ route('privacy.liability') }}" class="hover:underline" style="color: var(--ink-muted);">Liability</a>
            </div>
        </footer>

    </div>

    <script>
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
