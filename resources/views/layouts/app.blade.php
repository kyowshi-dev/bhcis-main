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
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/layout.js'])
    @livewireStyles

</head>

<body x-data="{
        sidebarOpen: false,
        sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
        openDropdown: null,
        showVitalsModal: false,
        lastDrawerTrigger: null,
        openDrawer(event) {
            this.lastDrawerTrigger = event?.currentTarget ?? null;
            this.openDropdown = null;
            this.sidebarOpen = true;
            this.$nextTick(() => {
                const target = this.$refs.sidebar?.querySelector('[data-drawer-close]');
                target?.focus();
            });
        },
        closeDrawer() {
            if (!this.sidebarOpen) return;
            this.sidebarOpen = false;
            this.$nextTick(() => this.lastDrawerTrigger?.focus());
        }
      }"
      :class="{ 'overflow-hidden': sidebarOpen }"
      class="min-h-screen overflow-x-hidden font-sans text-ink antialiased bg-page"
      x-on:open-vitals-modal.window="showVitalsModal = true"
      x-on:close-vitals-modal.window="showVitalsModal = false"
      x-on:keydown.escape.window="if (openDropdown) { openDropdown = null; } else if (sidebarOpen) { closeDrawer(); }"
      x-effect="localStorage.setItem('sidebarCollapsed', sidebarCollapsed)">

    @php
        /** @var \App\Models\User|null $authUser */
        $authUser = auth()->user();
        $can = fn (string $perm) => $authUser !== null && $authUser->hasPermission($perm);
    @endphp

    <div class="relative z-10 flex min-h-screen">

        @include('layouts.partials.sidebar')

        {{-- Main content area --}}
        <div class="flex-1 flex flex-col min-w-0">

            @include('layouts.partials.header')

            {{-- Page content --}}
            <main class="flex-1 px-[clamp(0.5rem,2vw,1.5rem)] pt-3 pb-2 lg:pt-4 lg:pb-3 overflow-auto">
                <div class="max-w-[76rem] mx-auto">

                    @php
                        $breadcrumbs = \App\Helpers\BreadcrumbHelper::getBreadcrumbs();
                    @endphp
                    @if (count($breadcrumbs) > 1)
                        <nav class="flex items-center gap-2 mb-3 py-1 animate-in opacity-0 delay-1 lg:px-2" aria-label="Breadcrumb trail">
                            @foreach ($breadcrumbs as $index => $crumb)
                                @if ($index > 0)
                                    <svg class="w-4 h-4 opacity-50" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M9 18l6-6-6-6"></path>
                                    </svg>
                                @endif
                                @if ($crumb['url'])
                                    <a href="{{ $crumb['url'] }}" class="font-medium transition-colors duration-200 hover:opacity-75 text-primary">{{ $crumb['name'] }}</a>
                                @else
                                    <span class="font-semibold text-ink">{{ $crumb['name'] }}</span>
                                @endif
                            @endforeach
                        </nav>
                    @endif

                    <div class="lg:px-2">
                        @yield('content')
                    </div>

                </div>
            </main>

            @include('layouts.partials.footer')
        </div>
    </div>

    <div id="liveConsultationToast" class="fixed bottom-5 right-5 z-[60] hidden max-w-[380px] rounded-3xl border border-border bg-surface-elevated shadow-lg overflow-hidden" aria-live="assertive" aria-atomic="true">
        <div class="p-5">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-teal-soft text-primary">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                    </svg>
                </span>
                <div class="min-w-0">
                    <p id="liveToastTitle" class="text-sm font-semibold text-ink">New Consultation Request</p>
                    <p id="liveToastSubtitle" class="text-xs text-ink-muted mt-1">Santa Ana Health Center - BHW</p>
                </div>
            </div>

            <div class="mt-4 rounded-3xl bg-teal-soft/50 p-4 text-ink">
                <p id="liveToastPatient" class="text-sm font-semibold"></p>
                <p id="liveToastDetails" class="text-xs text-ink-muted mt-1"></p>
                <p id="liveToastReason" class="mt-3 text-sm text-ink"></p>
            </div>

            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <button id="liveToastDecline" type="button" class="w-full rounded-2xl border border-border px-4 py-2 text-sm font-semibold text-ink-muted transition hover:bg-black/5 sm:w-auto">Cancel</button>
                <button id="liveToastAccept" type="button" class="w-full rounded-2xl bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-hover sm:w-auto">Accept & Open Case</button>
            </div>
        </div>
    </div>

    <div id="consultationCreateModal" x-show="$store.modals.consultation" x-transition.opacity.duration.200ms role="dialog" aria-modal="true" aria-labelledby="consultationCreateModalTitle" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeConsultationCreateModal()"></div>
        <div id="consultationCreateModalPanel" x-show="$store.modals.consultation" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="relative w-full max-w-4xl max-h-[90vh] overflow-y-auto rounded-2xl shadow-2xl focus:outline-none" style="background: var(--bg-surface-elevated);" tabindex="-1">
            <div id="consultationCreateModalContent"></div>
        </div>
    </div>

    <div id="printReferralConfirmModal" x-show="$store.modals.printReferral" x-transition.opacity.duration.200ms role="dialog" aria-modal="true" aria-labelledby="printReferralConfirmTitle" class="fixed inset-0 z-[60] flex items-center justify-center p-4" style="display: none;">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closePrintReferralConfirmModal()"></div>
        <div id="printReferralConfirmPanel" x-show="$store.modals.printReferral" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="relative w-full max-w-md rounded-2xl shadow-2xl p-6 focus:outline-none" style="background: var(--bg-surface-elevated);" tabindex="-1">
            <div class="flex items-start gap-3 mb-4">
                <div class="shrink-0 w-11 h-11 rounded-full flex items-center justify-center" style="background: var(--teal-soft); color: var(--primary);">
                    <i class="fa-solid fa-print text-lg" aria-hidden="true"></i>
                </div>
                <div>
                    <h2 id="printReferralConfirmTitle" class="font-display font-semibold text-lg" style="color: var(--ink);">Referral saved</h2>
                    <p class="text-sm mt-1" style="color: var(--ink-muted);">The outward referral has been recorded. Print the referral slip for the patient before they leave.</p>
                </div>
            </div>
            <div class="flex flex-wrap items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closePrintReferralConfirmModal()" class="px-4 py-2.5 rounded-xl border font-medium text-sm transition-colors hover:bg-black/[0.03]" style="border-color: var(--border); color: var(--ink-muted);">Close</button>
                <a id="printReferralConfirmLink" href="#" target="_blank" rel="noopener" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-white font-semibold text-sm transition hover:opacity-95" style="background: var(--primary);">
                    <i class="fa-solid fa-print" aria-hidden="true"></i> Print referral
                </a>
            </div>
        </div>
    </div>

    <script>
        window.BHCIS = {!! json_encode([
            'routes' => [
                'consultationsCreate' => route('consultations.create', ['patient' => '__PID__']),
                'sessionStatus' => route('session.status'),
                'sessionHeartbeat' => route('session.heartbeat'),
                'login' => route('login'),
                'liveRequests' => route('consultations.live-requests'),
            ],
            'sessionLifetimeMinutes' => (int) config('session.lifetime'),
            'gracefulErrors' => \App\Models\ApplicationSetting::gracefulErrorsEnabled(),
            'openConsultationFor' => session('open_consultation_for') ? (int) session('open_consultation_for') : null,
            'printReferralId' => session('print_referral_id') ? (int) session('print_referral_id') : null,
            'canPollLiveRequests' => auth()->check() && auth()->user()->hasPermission('consultations'),
        ], JSON_UNESCAPED_SLASHES) !!};
    </script>

    @livewireScripts
    @stack('page-modals')
    @stack('scripts')
</body>
</html>
