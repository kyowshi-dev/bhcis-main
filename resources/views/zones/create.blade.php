@extends('layouts.app')

@section('title', 'Add Zone')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <x-flash />

    <x-page-header
        title="Add zone"
        subtitle="Create a new geographic zone."
        icon="fa-solid fa-map-location-dot"
    >
        <x-slot name="actions">
            <a href="{{ route('zones.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl border border-border text-xs lg:text-sm font-medium text-ink transition hover:bg-black/5">Back to zones</a>
        </x-slot>
    </x-page-header>

    <form action="{{ route('zones.store') }}" method="POST" class="space-y-4">
        @csrf
        <div class="space-y-4">
            <div>
                <label for="zone_number" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Zone number <span style="color: var(--danger);">*</span></label>
                <input type="text" id="zone_number" name="zone_number" value="{{ old('zone_number') }}" required class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                @error('zone_number')<p class="mt-1 text-xs" style="color: var(--danger);">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="assigned_worker_id" class="block text-xs font-medium mb-1" style="color: var(--ink-muted);">Assign health worker</label>
                <select id="assigned_worker_id" name="assigned_worker_id" class="w-full rounded-lg border py-2 px-3 text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);">
                    <option value="">- Select a worker -</option>
                    @foreach ($healthWorkers as $worker)
                        <option value="{{ $worker->id }}" {{ old('assigned_worker_id') == $worker->id ? 'selected' : '' }}>
                            {{ fullName($worker->last_name, $worker->first_name) }}
                        </option>
                    @endforeach
                </select>
                @error('assigned_worker_id')<p class="mt-1 text-xs" style="color: var(--danger);">{{ $message }}</p>@enderror
            </div>
        </div>
        <button type="submit" class="px-4 py-2.5 rounded-xl text-white text-sm font-semibold transition duration-200 hover:shadow-md" style="background: var(--accent);">
            Add zone
        </button>
    </form>
</div>
@endsection