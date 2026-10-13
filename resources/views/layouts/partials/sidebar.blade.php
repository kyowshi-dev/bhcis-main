{{-- App sidebar: nav rail (desktop) + off-canvas drawer (mobile) --}}
{{-- Expects: $can (callable), uses body Alpine state: sidebarOpen, sidebarCollapsed, openDrawer, closeDrawer --}}
<div x-show="sidebarOpen"
     @click="closeDrawer()"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 z-40 bg-black/40 lg:hidden"
     style="display: none;"
     aria-hidden="true"></div>

<aside x-ref="sidebar"
       :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0', sidebarCollapsed ? 'sidebar-collapsed' : '']"
       class="app-sidebar transform fixed lg:sticky top-0 h-screen overflow-y-auto shrink-0 flex flex-col z-50 transition-all duration-300 ease-out border-r border-border shadow-md">

    <div class="flex items-center justify-between gap-2 p-3 lg:p-4 border-b border-border"
         :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : ''">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5 min-w-0" aria-label="BHCIS dashboard">
            <div class="logo-mark shrink-0" style="background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.25);">
                <img src="{{ asset('img/logo.svg') }}" alt="">
            </div>
            <span x-show="!sidebarCollapsed" x-cloak x-transition:enter="transition-opacity duration-150"
                  class="min-w-0 leading-tight">
                <span class="block font-display font-semibold text-sm text-white">BHCIS</span>
                <span class="block text-[10px] font-medium text-white/60">Sta. Ana</span>
            </span>
        </a>
        <button type="button"
                data-drawer-close
                @click="closeDrawer()"
                x-show="!sidebarCollapsed"
                x-cloak
                class="lg:hidden shrink-0 p-2.5 -m-1 rounded-lg hover:bg-white/10 transition-colors text-white/90"
                aria-label="Close menu">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <button type="button"
                @click="sidebarCollapsed = !sidebarCollapsed"
                x-show="!sidebarCollapsed"
                x-cloak
                class="hidden lg:block shrink-0 p-2 -m-1 rounded-lg hover:bg-white/10 transition-colors text-white/70 hover:text-white"
                :aria-label="sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                :aria-expanded="(!sidebarCollapsed).toString()">
            <svg class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M11 19l-7-7 7-7"/>
                <path d="M18 19l-7-7 7-7"/>
            </svg>
        </button>
        {{-- Collapsed rail: expand-on-click logo area --}}
        <button type="button"
                @click="sidebarCollapsed = false"
                x-show="sidebarCollapsed"
                x-cloak
                class="hidden lg:flex p-1 rounded-lg hover:bg-white/10 transition-colors text-white/70 hover:text-white -mr-1"
                aria-label="Expand sidebar"
                aria-expanded="false">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M13 5l7 7-7 7"/><path d="M6 5l7 7-7 7"/>
            </svg>
        </button>
    </div>

    {{-- Mobile-only search (header search is sm+; drawer is the small-screen home for it) --}}
    <div class="lg:hidden relative p-2 border-b border-white/10" x-data="globalSearch({ allowHouseholds: {{ $can('household') ? 'true' : 'false' }} })">
        <div class="relative">
            <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-white/50" aria-hidden="true"></i>
            <input type="search" x-model="query" @input.debounce.300ms="search()" @click.away="close()"
                   @keydown.escape="close()"
                   placeholder="Search patients, households..."
                   class="w-full rounded-lg border border-white/20 bg-white/10 pl-9 pr-3 py-2 text-sm text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-white/30 transition-all"
                   aria-label="Search patients and households">
        </div>
        <div x-show="results.length > 0" x-transition class="absolute left-0 right-0 top-full mt-2 rounded-xl border border-border shadow-lg bg-surface-elevated z-50 max-h-72 overflow-y-auto" role="listbox">
            <template x-for="(item, index) in results" :key="item.url">
                <a :href="item.url" class="flex items-center gap-3 px-4 py-2.5 text-sm text-ink hover:bg-black/5 transition-colors border-b border-border last:border-0"
                   :class="{ 'bg-teal-soft': index === activeIndex }" role="option">
                    <span class="flex-1 min-w-0">
                        <span x-html="highlightMatch(item.label, query)" class="block truncate"></span>
                        <span x-show="item.subtext" x-text="item.subtext" class="block text-xs text-ink-muted truncate"></span>
                    </span>
                </a>
            </template>
        </div>
    </div>

    <nav class="flex-1 p-2 space-y-0.5 overflow-y-auto" aria-label="Main navigation">

        @php
            $roleName = strtolower(trim((string) (auth()->user()?->role->role_name ?? auth()->user()?->healthWorker->role ?? '')));
            $dashboardLabel = $roleName === 'midwife' ? 'Maternal Register' : 'Dashboard';
        @endphp
        <x-layouts.nav-link url="{{ route('dashboard') }}" :label="$dashboardLabel" icon="fa-solid fa-house"
                            :active="request()->routeIs('dashboard')" />

        @if ($can('patients') || $can('household') || $can('zones'))
            <p class="sidebar-category-label px-2.5 pt-4 pb-1.5 text-[11px] font-semibold uppercase tracking-wider text-white/55" aria-hidden="true">Community</p>
            @if ($can('patients'))
                <x-layouts.nav-link url="{{ route('patients.index') }}" label="Patients" icon="fa-solid fa-user-injured"
                                    :active="request()->routeIs('patients*')" />
            @endif

            @if ($can('household'))
                <x-layouts.nav-link url="{{ route('households.index') }}" label="Households" icon="fa-solid fa-house-chimney"
                                    :active="request()->routeIs('households*')" />
            @endif

            @if ($can('zones'))
                <x-layouts.nav-link url="{{ route('zones.index') }}" label="Zones" icon="fa-solid fa-map-location-dot"
                                    :active="request()->routeIs('zones*')" />
            @endif
        @endif

        @if ($can('consultations') || $can('immunizations') || $can('maternal') || $can('nurse_batch_entry'))
            <p class="sidebar-category-label px-2.5 pt-4 pb-1.5 text-[11px] font-semibold uppercase tracking-wider text-white/55" aria-hidden="true">Health Care Services</p>
            @if ($can('nurse_batch_entry'))
                <x-layouts.nav-link url="{{ route('batch-entry.index') }}" label="Batch Entry" icon="fa-solid fa-file-medical"
                                    :active="request()->routeIs('batch-entry*')" />
            @endif

            @if ($can('consultations'))
                <x-layouts.nav-link url="{{ route('consultations.index') }}" label="Consultations" icon="fa-solid fa-stethoscope"
                                    :active="request()->routeIs('consultations*')" />
            @endif

            @if ($can('immunizations'))
                <x-layouts.nav-link url="{{ route('immunizations.index') }}" label="Immunizations" icon="fa-solid fa-syringe"
                                    :active="request()->routeIs('immunizations*')" />
            @endif

            @if ($can('maternal'))
                @php
                    $maternalSectionUrl = request()->routeIs('maternal.postnatal*')
                        ? route('maternal.postnatal.index')
                        : (request()->routeIs('maternal.family-planning*')
                            ? route('maternal.family-planning.index')
                            : route('maternal.prenatal.index'));
                @endphp
                <x-layouts.nav-link url="{{ $maternalSectionUrl }}" label="Maternal Care" icon="fa-solid fa-person-pregnant"
                                    :active="request()->routeIs('maternal*')" />
            @endif

            @if ($can('referrals'))
                <x-layouts.nav-link url="{{ route('referrals.index') }}" label="Referrals" icon="fa-solid fa-arrow-up-right-from-square"
                                    :active="request()->routeIs('referrals*')" />
            @endif
        @endif

        @if ($can('medicines') || $can('reports'))
            <p class="sidebar-category-label px-2.5 pt-4 pb-1.5 text-[11px] font-semibold uppercase tracking-wider text-white/55" aria-hidden="true">Reports &amp; Inventory</p>
            @if ($can('medicines'))
                <x-layouts.nav-link url="{{ route('medicines.index') }}" label="Medicines" icon="fa-solid fa-pills"
                                    :active="request()->routeIs('medicines*')" />
            @endif

            @if ($can('reports'))
                <x-layouts.nav-link url="{{ route('reports.index') }}" label="Reports" icon="fa-solid fa-file-lines"
                                    :active="request()->routeIs('reports*')" />
            @endif
        @endif

        @if ($can('users'))
            <p class="sidebar-category-label px-2.5 pt-4 pb-1.5 text-[11px] font-semibold uppercase tracking-wider text-white/55" aria-hidden="true">Administration</p>
            <x-layouts.nav-link url="{{ route('users.index') }}" label="Users" icon="fa-solid fa-user-gear"
                                :active="request()->routeIs('users*')" />
            <x-layouts.nav-link url="{{ route('roles.index') }}" label="Roles" icon="fa-solid fa-user-shield"
                                :active="request()->routeIs('roles*')" />
            <x-layouts.nav-link url="{{ route('activity-logs.index') }}" label="Activity Logs" icon="fa-solid fa-clock-rotate-left"
                                :active="request()->routeIs('activity-logs*')" />
            <x-layouts.nav-link url="{{ route('privacy.index') }}" label="Privacy Settings" icon="fa-solid fa-shield-halved"
                                :active="request()->routeIs('privacy.index')" />
        @endif

        <div class="pt-2 mt-2 border-t border-white/10">
            <x-layouts.nav-link url="{{ route('settings.index') }}" label="Settings" icon="fa-solid fa-gear"
                                :active="request()->routeIs('settings*')" />
        </div>
    </nav>
</aside>
