@extends('layouts.app')

@section('title', 'Patients')

@section('content')
@php
    $patientStatus = $patientStatus ?? 'active';
    $patientFiltersActive = request()->filled('sort') || request()->filled('dir');

    $patientTabUrl = function (string $status) use ($patientSort, $patientDir) {
        return route('patients.index', array_filter([
            'status' => $status === 'active' ? null : $status,
            'sort' => $patientSort,
            'dir' => $patientDir,
            'per_page' => request('per_page'),
        ]));
    };

    $patientSortNextUrl = function (string $column) use ($patientStatus, $patientSort, $patientDir) {
        if ($patientSort === $column) {
            $nextDir = $patientDir === 'asc' ? 'desc' : 'asc';
        } else {
            $nextDir = match ($column) {
                'name', 'gender' => 'asc',
                default => 'desc',
            };
        }

        return route('patients.index', array_filter([
            'status' => $patientStatus === 'active' ? null : $patientStatus,
            'sort' => $column,
            'dir' => $nextDir,
            'per_page' => request('per_page'),
        ]));
    };
@endphp
<div class="space-y-5 lg:space-y-6">
    <x-page-header
        title="Patient Records"
        subtitle="Search and manage patient information."
        icon="fa-solid fa-user-group"
    >
        <x-slot name="actions">
            <x-btn href="{{ route('patients.create') }}" icon="fa-solid fa-user-plus">
                Enrol New Patient
            </x-btn>
        </x-slot>
    </x-page-header>

    <x-flash />

    <div class="rounded-xl" x-data="patientSearch()">
        <div class="relative">
            <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none" style="color: var(--ink-subtle);">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            </span>
            <input type="text" x-model="query" @input.debounce.300ms="search()"
                   placeholder="Search"
                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border text-sm focus:outline-none focus:ring-2 transition"
                   style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary);"
                   autocomplete="off">
        </div>
        <div x-show="results.length > 0" class="mt-3 rounded-lg border overflow-hidden" style="display: none; border-color: var(--border); background: var(--bg-surface-elevated); box-shadow: var(--shadow-md);">
            <ul>
                <template x-for="patient in results" :key="patient.id">
                    <li class="border-b last:border-0 transition-colors hover:bg-black/[0.03]">
                        <a :href="'/patients/' + patient.id" class="block px-4 py-2.5">
                            <div class="font-medium text-sm" style="color: var(--ink);" x-text="patient.text"></div>
                            <div class="text-xs mt-0.5" style="color: var(--ink-muted);" x-text="patient.subtext"></div>
                        </a>
                    </li>
                </template>
            </ul>
        </div>
        <div x-show="query.length > 1 && results.length === 0 && !loading" class="mt-3 rounded-lg border p-6 text-center" style="display: none; border-color: var(--border); background: var(--bg-surface);">
            <p class="text-sm font-medium mt-2" style="color: var(--ink);">No results found</p>
            <p class="text-xs mt-1" style="color: var(--ink-muted);">Try a different search term or patient ID</p>
        </div>
        <div x-show="loading" class="mt-3 rounded-lg border overflow-hidden" style="display: none; border-color: var(--border); background: var(--bg-surface-elevated);">
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
    </div>

    <nav class="flex flex-wrap items-center gap-2" aria-label="Patient list view">
        <a href="{{ $patientTabUrl('active') }}"
           @if ($patientStatus === 'active') aria-current="page" @endif
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold transition {{ $patientStatus === 'active' ? 'bg-teal-soft text-primary border border-primary/30' : 'border border-border text-ink-muted hover:bg-black/5' }}">
            <i class="fa-solid fa-user-group" aria-hidden="true"></i>
            Active ({{ $patientCounts['active'] }})
        </a>
        <a href="{{ $patientTabUrl('archived') }}"
           @if ($patientStatus === 'archived') aria-current="page" @endif
           class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold transition {{ $patientStatus === 'archived' ? 'bg-amber-soft text-amber border border-amber/30' : 'border border-border text-ink-muted hover:bg-black/5' }}">
            <i class="fa-solid fa-box-archive" aria-hidden="true"></i>
            Archived ({{ $patientCounts['archived'] }})
        </a>
    </nav>

    <div class="rounded-xl border overflow-hidden" style="background: var(--bg-surface-elevated); border-color: var(--border); box-shadow: var(--shadow-sm);">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-xs lg:text-sm">
                <thead>
                    <tr style="background: var(--teal-soft);">
                        <th class="px-4 py-3 font-semibold uppercase tracking-wider whitespace-nowrap" style="color: var(--ink-muted);">
                            <a href="{{ $patientSortNextUrl('id') }}" class="inline-flex items-center gap-1.5 hover:underline focus:outline-none focus:ring-2 rounded" style="color: inherit; --tw-ring-color: var(--primary);">
                                ID
                                @if ($patientSort === 'id')
                                    <i class="fa-solid {{ $patientDir === 'asc' ? 'fa-chevron-up' : 'fa-chevron-down' }} text-[10px]" aria-hidden="true"></i>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-3 font-semibold uppercase tracking-wider whitespace-nowrap" style="color: var(--ink-muted);">
                            <a href="{{ $patientSortNextUrl('name') }}" class="inline-flex items-center gap-1.5 hover:underline focus:outline-none focus:ring-2 rounded" style="color: inherit; --tw-ring-color: var(--primary);">
                                Name
                                @if ($patientSort === 'name')
                                    <i class="fa-solid {{ $patientDir === 'asc' ? 'fa-chevron-up' : 'fa-chevron-down' }} text-[10px]" aria-hidden="true"></i>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-3 font-semibold uppercase tracking-wider whitespace-nowrap" style="color: var(--ink-muted);">
                            <a href="{{ $patientSortNextUrl('age') }}" class="inline-flex items-center gap-1.5 hover:underline focus:outline-none focus:ring-2 rounded" style="color: inherit; --tw-ring-color: var(--primary);">
                                Age
                                @if ($patientSort === 'age')
                                    <i class="fa-solid {{ $patientDir === 'asc' ? 'fa-chevron-up' : 'fa-chevron-down' }} text-[10px]" aria-hidden="true"></i>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-3 font-semibold uppercase tracking-wider whitespace-nowrap hidden sm:table-cell" style="color: var(--ink-muted);">
                            <a href="{{ $patientSortNextUrl('gender') }}" class="inline-flex items-center gap-1.5 hover:underline focus:outline-none focus:ring-2 rounded" style="color: inherit; --tw-ring-color: var(--primary);">
                                Gender
                                @if ($patientSort === 'gender')
                                    <i class="fa-solid {{ $patientDir === 'asc' ? 'fa-chevron-up' : 'fa-chevron-down' }} text-[10px]" aria-hidden="true"></i>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-3 font-semibold uppercase tracking-wider whitespace-nowrap hidden md:table-cell" style="color: var(--ink-muted);">Phone</th>
                        @if ($patientStatus === 'archived')
                            <th class="px-4 py-3 font-semibold uppercase tracking-wider whitespace-nowrap hidden lg:table-cell" style="color: var(--ink-muted);">Archived</th>
                        @else
                            <th class="px-4 py-3 font-semibold uppercase tracking-wider whitespace-nowrap hidden lg:table-cell" style="color: var(--ink-muted);">
                                <a href="{{ $patientSortNextUrl('last_visit') }}" class="inline-flex items-center gap-1.5 hover:underline focus:outline-none focus:ring-2 rounded" style="color: inherit; --tw-ring-color: var(--primary);">
                                    Last visit
                                    @if ($patientSort === 'last_visit')
                                        <i class="fa-solid {{ $patientDir === 'asc' ? 'fa-chevron-up' : 'fa-chevron-down' }} text-[10px]" aria-hidden="true"></i>
                                    @endif
                                </a>
                            </th>
                        @endif
                        <th class="px-4 py-3 font-semibold uppercase tracking-wider text-right whitespace-nowrap" style="color: var(--ink-muted);">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[var(--border)]">
                    @forelse ($patients as $patient)
                        <tr class="transition-colors hover:bg-black/5">
                            <td class="px-4 py-2.5 font-medium whitespace-nowrap" style="color: var(--ink);">
                                {{ \App\Helpers\PatientCode::format((int) $patient->id) }}
                            </td>
                            <td class="px-4 py-2.5" style="color: var(--ink);">
                                <div class="font-medium">{{ fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix) }}</div>
                                <div class="text-xs mt-0.5 line-clamp-2 text-ink-muted" style="color: var(--ink-muted);">
                                    {{ \Carbon\Carbon::parse($patient->date_of_birth)->format('M j, Y') }}
                                    @if (! empty($patient->residential_address))
                                        <span class="text-ink-subtle"> · </span>{{ \Illuminate\Support\Str::limit($patient->residential_address, 52) }}
                                    @endif
                                </div>
                                <div class="text-xs sm:hidden mt-0.5" style="color: var(--ink-muted);">{{ $patient->sex }}</div>
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap" style="color: var(--ink-muted);">{{ \Carbon\Carbon::parse($patient->date_of_birth)->age }}</td>
                            <td class="px-4 py-2.5 whitespace-nowrap hidden sm:table-cell" style="color: var(--ink-muted);">{{ $patient->sex }}</td>
                            <td class="px-4 py-2.5 whitespace-nowrap hidden md:table-cell" style="color: var(--ink-muted);">{{ $patient->contact_number ?? '-' }}</td>
                            @if ($patientStatus === 'archived')
                                <td class="px-4 py-2.5 whitespace-nowrap hidden lg:table-cell" style="color: var(--ink-muted);">
                                    {{ $patient->deleted_at ? \Carbon\Carbon::parse($patient->deleted_at)->format('M j, Y') : '-' }}
                                </td>
                            @else
                                <td class="px-4 py-2.5 whitespace-nowrap hidden lg:table-cell" style="color: var(--ink-muted);">
                                    @if ($patient->last_visit) {{ \Carbon\Carbon::parse($patient->last_visit)->format('Y-m-d') }} @else - @endif
                                </td>
                            @endif
                            <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                @if ($patient->trashed())
                                    @if (auth()->user()?->hasPermission('patients_destroy'))
                                        <form action="{{ route('patients.restore', $patient->id) }}" method="POST" class="inline" id="restore-form-{{ $patient->id }}">
                                            @csrf
                                            <button type="button" class="font-semibold text-sm transition-colors hover:underline" style="color: var(--primary);" onclick="confirmRestore({{ $patient->id }})">Restore</button>
                                        </form>
                                    @else
                                        <span aria-label="No actions available" style="color: var(--ink-subtle);">&mdash;</span>
                                    @endif
                                @else
                                    <a href="{{ route('patients.show', $patient->id) }}" class="font-semibold text-sm transition-colors hover:underline" style="color: var(--primary);">View</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-4">
                                @if ($patientStatus === 'archived')
                                    <x-empty-state
                                        icon="fa-solid fa-box-archive"
                                        title="No archived patients"
                                        description="Patients you archive from a patient profile will appear here so they can be restored."
                                    />
                                @elseif ($patientFiltersActive)
                                    <x-empty-state
                                        icon="fa-solid fa-filter-circle-xmark"
                                        title="No records found"
                                        description="No patients match the current sort."
                                    >
                                        <x-btn href="{{ route('patients.index', array_filter(['status' => $patientStatus === 'active' ? null : $patientStatus, 'per_page' => request('per_page')])) }}" variant="outlined" icon="fa-solid fa-arrow-rotate-left">
                                            Clear sort
                                        </x-btn>
                                    </x-empty-state>
                                @else
                                    <x-empty-state
                                        icon="fa-solid fa-clipboard-list"
                                        title="No patient records yet"
                                        description="Start by registering a patient to track their health data."
                                    >
                                        <x-btn href="{{ route('patients.create') }}" icon="fa-solid fa-plus">
                                            Register first patient
                                        </x-btn>
                                    </x-empty-state>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($patients->total() > 0)
            <div class="border-t px-4 py-3" style="border-color: var(--border);">
                <x-pagination :paginator="$patients" />
            </div>
        @endif
    </div>
</div>

<script>
    function patientSearch() {
        return {
            query: '',
            results: [],
            loading: false,
            async search() {
                if (this.query.length < 2) { this.results = []; return; }
                this.loading = true;
                try {
                    const response = await safeFetch(`{{ route('search.patients') }}?query=${this.query}`);
                    const data = response.ok ? await response.json() : [];
                    this.results = data.map(item => ({
                        ...item,
                        text: item.text.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' '),
                    }));
                } catch (e) { console.error('Search failed:', e); }
                this.loading = false;
            },
        };
    }

    window.confirmRestore = function(patientId) {
        Swal.fire({
            title: 'Restore this patient?',
            text: 'This will restore the patient record and make it visible again.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Restore',
            cancelButtonText: 'Cancel',
            confirmButtonColor: 'var(--primary)',
            cancelButtonColor: 'var(--ink-muted)',
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.getElementById('restore-form-' + patientId);
                if (form) form.submit();
            }
        });
    };
</script>

@if(session('success') && session('new_patient_id'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const patientId = {{ session('new_patient_id') }};
        Swal.fire({
            title: 'Patient Registered Successfully!',
            text: 'What would you like to do next?',
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: 'Proceed to Consultation',
            cancelButtonText: 'Back to Home',
            confirmButtonColor: 'var(--primary)',
            cancelButtonColor: 'var(--ink-muted)',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                openConsultationCreateModal(patientId);
            } else {
                // Stay on the current page (patients index)
            }
        });
    });
</script>
@endif

@endsection
