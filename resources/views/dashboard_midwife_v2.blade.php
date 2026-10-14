@extends('layouts.app')

@section('title', 'Maternal and Family Register')

@section('content')
<div class="space-y-5 lg:space-y-6">
    {{-- Page Header --}}
    <div class="animate-in opacity-0 delay-1 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Maternal and Family Register</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">Digital Target Client List for Sta. Ana — search residents and open the program registers.</p>
        </div>

        @include('dashboard.partials.date-chip')
    </div>

    <x-flash />

    @if (auth()->user()?->hasPermission('maternal'))
    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 animate-in opacity-0 delay-2">
        <x-kpi-card
            label="Active Pregnancies"
            :value="$activePregnancies"
            icon="fa-solid fa-person-pregnant"
            tone="primary"
            :href="route('maternal.prenatal.index')"
            :hrefLabel="'Open prenatal register'"
            :animateDelay="3"
        />

        <x-kpi-card
            label="Postnatal Mothers"
            :value="$postnatalMothers"
            icon="fa-solid fa-baby"
            tone="primary"
            :href="route('maternal.postnatal.index')"
            :hrefLabel="'Open postnatal register'"
            :animateDelay="4"
        />

        <x-kpi-card
            label="FP Clients"
            :value="$fpClients"
            icon="fa-solid fa-pills"
            tone="blue"
            :href="route('maternal.family-planning.index')"
            :hrefLabel="'Open FP register'"
            :animateDelay="5"
        />

        <x-kpi-card
            label="Follow-ups Due or Overdue"
            :value="$followUpsDue"
            icon="fa-solid fa-calendar-check"
            :tone="$followUpsDue > 0 ? 'amber' : 'primary'"
            :zeroState="$followUpsDue === 0 ? 'Prenatal, FP, and postnatal' : null"
            :animateDelay="6"
        />
    </div>
    @endif

    {{-- Quick Search --}}
    <div class="animate-in opacity-0 delay-3 rounded-xl border p-4" x-data="residentSearch()"
         style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
        <div class="flex flex-col lg:flex-row lg:items-center gap-3">
            <div class="relative flex-1 min-w-0">
                <label for="resident-search" class="sr-only">Search resident by name</label>
                <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none" style="color: var(--ink-subtle);">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                </span>
                <input id="resident-search" type="search" x-model="query" @input.debounce.300ms="search()"
                       placeholder="Search resident by name…"
                       class="w-full pl-10 pr-4 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 transition"
                       style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--accent-blue);"
                       autocomplete="off">
            </div>
            @if (auth()->user()?->hasPermission('patients'))
                <a href="{{ route('patients.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold text-white transition hover:opacity-90 shrink-0" style="background: var(--primary);">
                    <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
                    Register New Resident
                </a>
            @endif
        </div>
        <div x-show="loading" x-transition class="mt-3 rounded-lg border overflow-hidden" style="display: none; border-color: var(--border); background: var(--bg-surface-elevated);">
            <div class="px-4 py-3 border-b border-border" aria-hidden="true">
                <div class="h-3.5 w-24 rounded bg-surface animate-pulse"></div>
            </div>
            @for ($i = 0; $i < 3; $i++)
                <div class="px-4 py-3 border-b border-border last:border-0" aria-hidden="true">
                    <div class="h-3.5 w-40 rounded bg-surface animate-pulse"></div>
                    <div class="mt-1.5 h-3 w-56 rounded bg-surface animate-pulse"></div>
                </div>
            @endfor
        </div>
        <div x-show="results.length > 0" x-transition class="mt-3 rounded-lg border overflow-hidden" style="display: none; border-color: var(--border); background: var(--bg-surface-elevated); box-shadow: var(--shadow-md);">
            <ul>
                <template x-for="patient in results" :key="patient.id">
                    <li class="border-b last:border-0 transition-colors hover:bg-black/[0.03]">
                        <a :href="`/patients/${patient.id}`" class="block w-full text-left px-4 py-2.5">
                            <div class="font-medium text-sm" style="color: var(--ink);" x-text="patient.text"></div>
                            <div class="text-xs mt-0.5" style="color: var(--ink-muted);" x-text="patient.subtext"></div>
                        </a>
                    </li>
                </template>
            </ul>
        </div>
        <div x-show="query.length > 1 && results.length === 0 && !loading" x-transition class="mt-3 rounded-lg border p-6 text-center" style="display: none; border-color: var(--border); background: var(--bg-surface);">
            <div class="flex justify-center mb-2"><i class="fa-solid fa-user-magnifying-glass text-3xl" style="color: var(--ink-subtle);" aria-hidden="true"></i></div>
            <p class="text-sm font-medium" style="color: var(--ink);">No resident found</p>
            <p class="text-xs mt-1 mb-3" style="color: var(--ink-muted);">Try a different name, or register the resident first.</p>
            @if (auth()->user()?->hasPermission('patients'))
                <a href="{{ route('patients.create') }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg text-xs font-semibold text-white transition hover:opacity-90" style="background: var(--primary);">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i> Register new resident
                </a>
            @endif
        </div>
    </div>

    {{-- Quick Links --}}
    @if (auth()->user()?->hasPermission('maternal'))
    <div class="animate-in opacity-0 delay-4" x-data="registerPanels()">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <button type="button"
                    id="register-toggle-prenatal"
                    @click="toggle('prenatal')" @keydown.space.prevent="toggle('prenatal')"
                    :aria-expanded="open === 'prenatal'" aria-expanded="false"
                    aria-controls="register-panel-prenatal"
                    class="flex items-center gap-3 rounded-xl border p-4 transition hover:bg-black/[0.02] hover:shadow-md"
                    style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg" style="background: var(--teal-soft); color: var(--primary);">
                    <i class="fa-solid fa-person-pregnant" aria-hidden="true"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold" style="color: var(--ink);">Open Prenatal Register</p>
                    <p class="text-xs mt-0.5 truncate" style="color: var(--ink-muted);">Active pregnancies, EDC, next visit</p>
                </div>
                <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200 shrink-0"
                   :class="{ 'rotate-180': open === 'prenatal' }" style="color: var(--ink-muted);" aria-hidden="true"></i>
            </button>

            <button type="button"
                    id="register-toggle-fp"
                    @click="toggle('fp')" @keydown.space.prevent="toggle('fp')"
                    :aria-expanded="open === 'fp'" aria-expanded="false"
                    aria-controls="register-panel-fp"
                    class="flex items-center gap-3 rounded-xl border p-4 transition hover:bg-black/[0.02] hover:shadow-md"
                    style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg" style="background: var(--accent-blue-soft); color: var(--accent-blue);">
                    <i class="fa-solid fa-pills" aria-hidden="true"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold" style="color: var(--ink);">Open FP Register</p>
                    <p class="text-xs mt-0.5 truncate" style="color: var(--ink-muted);">Active acceptors and follow-up schedule</p>
                </div>
                <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200 shrink-0"
                   :class="{ 'rotate-180': open === 'fp' }" style="color: var(--ink-muted);" aria-hidden="true"></i>
            </button>

            <button type="button"
                    id="register-toggle-postnatal"
                    @click="toggle('postnatal')" @keydown.space.prevent="toggle('postnatal')"
                    :aria-expanded="open === 'postnatal'" aria-expanded="false"
                    aria-controls="register-panel-postnatal"
                    class="flex items-center gap-3 rounded-xl border p-4 transition hover:bg-black/[0.02] hover:shadow-md"
                    style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-lg" style="background: var(--teal-soft); color: var(--primary);">
                    <i class="fa-solid fa-baby" aria-hidden="true"></i>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold" style="color: var(--ink);">Open Postnatal Register</p>
                    <p class="text-xs mt-0.5 truncate" style="color: var(--ink-muted);">24h / 7d / 14d / 28d follow-ups</p>
                </div>
                <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200 shrink-0"
                   :class="{ 'rotate-180': open === 'postnatal' }" style="color: var(--ink-muted);" aria-hidden="true"></i>
            </button>
        </div>

        <div id="register-panel-prenatal" x-show="open === 'prenatal'" x-collapse
             style="display: none;" class="mt-3" role="region" aria-labelledby="register-toggle-prenatal">
            <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
                @include('dashboard.partials.register-preview-prenatal', [
                    'rows' => $prenatalPreview ?? collect(),
                    'total' => $activePregnancies,
                ])
            </div>
        </div>

        <div id="register-panel-fp" x-show="open === 'fp'" x-collapse
             style="display: none;" class="mt-3" role="region" aria-labelledby="register-toggle-fp">
            <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
                @include('dashboard.partials.register-preview-fp', [
                    'rows' => $fpPreview ?? collect(),
                    'total' => $fpClients,
                ])
            </div>
        </div>

        <div id="register-panel-postnatal" x-show="open === 'postnatal'" x-collapse
             style="display: none;" class="mt-3" role="region" aria-labelledby="register-toggle-postnatal">
            <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border); box-shadow: var(--shadow-sm);">
                @include('dashboard.partials.register-preview-postnatal', [
                    'rows' => $postnatalPreview ?? collect(),
                    'total' => $postnatalMothers,
                ])
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function residentSearch() {
        return {
            query: '',
            results: [],
            loading: false,
            async search() {
                if (this.query.length < 2) { this.results = []; return; }
                this.loading = true;
                try {
                    const response = await safeFetch(`{{ route('search.patients') }}?query=${encodeURIComponent(this.query)}`);
                    this.results = response.ok ? await response.json() : [];
                } catch (e) {
                    console.error('Search failed:', e);
                    this.results = [];
                }
                this.loading = false;
            },
        };
    }

    function registerPanels() {
        return {
            open: null,
            toggle(key) {
                const opening = this.open !== key;
                this.open = opening ? key : null;
                if (!opening) return;
                this.$nextTick(() => {
                    const panel = document.getElementById('register-panel-' + key);
                    if (!panel) return;
                    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    panel.scrollIntoView({ behavior: reduced ? 'auto' : 'smooth', block: 'nearest' });
                });
            },
        };
    }
</script>
@endpush