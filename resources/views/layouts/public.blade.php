<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'BHCIS') - Sta. Ana</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('img/logo.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen font-sans text-ink antialiased" style="background: var(--bg-page);">
    <div class="min-h-screen flex flex-col">

        <main class="flex-1">
            <div class="max-w-3xl mx-auto px-4 py-8">
                @yield('content')
            </div>
        </main>

        <footer class="shrink-0 sticky bottom-0 border-t border-border z-10" style="background: var(--bg-surface); color: var(--ink-subtle);">
            <div class="max-w-3xl mx-auto px-4 py-4 text-center text-xs" style="color: var(--ink-muted);">
                &copy; {{ date('Y') }} Barangay Sta. Ana Health Center. All rights reserved.
                <span class="mx-1">|</span>
                <a href="{{ route('privacy.policy') }}" class="hover:underline" style="color: var(--ink-muted);">Privacy Policy</a>
                <span class="mx-1">|</span>
                <a href="{{ route('privacy.liability') }}" class="hover:underline" style="color: var(--ink-muted);">Liability</a>
            </div>
        </footer>

    </div>
</body>
</html>
