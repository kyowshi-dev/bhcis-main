{{-- App footer: quiet facility line + legal links --}}
<footer class="shrink-0 border-t border-border bg-surface">
    <div class="px-4 lg:px-6 py-3 flex flex-col sm:flex-row items-center justify-center sm:justify-between gap-1.5 sm:gap-3">
        <p class="text-xs text-ink-subtle text-center sm:text-left">
            &copy; {{ date('Y') }} Barangay Sta. Ana Health Center. All rights reserved.
        </p>
        <nav class="flex items-center gap-4 text-xs" aria-label="Footer">
            <a href="{{ route('privacy.policy') }}" class="text-ink-muted hover:text-ink transition-colors">Privacy Policy</a>
            <a href="{{ route('privacy.liability') }}" class="text-ink-muted hover:text-ink transition-colors">Liability</a>
        </nav>
    </div>
</footer>
