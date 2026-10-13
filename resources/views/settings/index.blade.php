@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <div>
        <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Settings</h1>
        <p class="text-sm mt-1" style="color: var(--ink-muted);">Manage your account and system backups.</p>
    </div>

    <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
        <h2 class="font-display font-semibold text-sm mb-4" style="color: var(--ink);">System Settings</h2>

        <form method="POST" action="{{ route('settings.update') }}" class="space-y-4">
            @csrf

            <div>
                <x-field name="projected_population" type="number" min="0" step="1" placeholder="e.g., 50000" :value="$projectedPopulation">
                    <x-slot name="label">Projected Population</x-slot>
                    <x-slot name="help">Projected population for the barangay (from PSA/LGU). Used for CBR calculation in natality reports.</x-slot>
                    <x-slot name="control">
                        <input type="number" name="projected_population" id="projected_population" min="0" step="1" placeholder="e.g., 50000" value="{{ $projectedPopulation }}" class="w-full rounded-lg border py-2 text-xs lg:text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                    </x-slot>
                </x-field>
            </div>

            <div>
                <x-field name="session_timeout" type="number" min="5" max="2880" step="1" placeholder="120" :value="$sessionTimeout">
                    <x-slot name="label">Session Timeout (minutes)</x-slot>
                    <x-slot name="help">Minutes of inactivity before automatic logout. Minimum 5, maximum 2880 (48 hours).</x-slot>
                    <x-slot name="control">
                        <input type="number" name="session_timeout" id="session_timeout" min="5" max="2880" step="1" placeholder="120" value="{{ $sessionTimeout }}" class="w-full rounded-lg border py-2 text-xs lg:text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                    </x-slot>
                </x-field>
            </div>

            <button type="submit" class="px-4 py-2 rounded-lg text-white text-sm font-medium transition-colors hover:opacity-90" style="background: var(--primary);">Save Settings</button>
        </form>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-5">
        <a href="{{ route('settings.account') }}"
           class="block p-5 lg:p-6 rounded-xl border transition-all duration-200 hover:shadow-md hover:scale-[1.01]" style="background: var(--bg-surface); border-color: var(--border);">
            <h3 class="font-display font-semibold text-base mb-1" style="color: var(--ink);">Account management</h3>
            <p class="text-sm" style="color: var(--ink-muted);">Change your password and manage your login credentials.</p>
        </a>
        @if(auth()->user()->hasPermission('users'))
            <a href="{{ route('settings.backups') }}"
               class="block p-5 lg:p-6 rounded-xl border transition-all duration-200 hover:shadow-md hover:scale-[1.01]" style="background: var(--bg-surface); border-color: var(--border);">
                <h3 class="font-display font-semibold text-base mb-1" style="color: var(--ink);">Backups</h3>
                <p class="text-sm" style="color: var(--ink-muted);">Export the database for backup or transfer.</p>
            </a>
        @endif
    </div>
</div>
@endsection
