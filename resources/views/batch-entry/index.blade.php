@extends('layouts.app')

@section('title', 'Batch Data Entry')

@section('content')
<div class="max-w-5xl mx-auto space-y-3 lg:space-y-4" x-data="batchEntry()">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-lg lg:text-xl font-bold" style="color: var(--ink);">Batch Data Entry</h1>
            <p class="text-xs lg:text-sm" style="color: var(--ink-muted);">Digitize paper forms from BHW field visits. Enter patient data from paper records into the system.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium" style="background: var(--teal-soft); color: var(--primary);">
                <i class="fa-solid fa-file-medical" aria-hidden="true"></i>
                Paper-to-Digital Mode
            </span>
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if (session('success'))
        <div class="rounded-xl bg-primary/10 border border-primary/30 px-4 py-3 text-sm text-primary" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <i class="fa-solid fa-check-circle" aria-hidden="true"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button @click="show = false" class="text-primary/60 hover:text-primary"><i class="fa-solid fa-times" aria-hidden="true"></i></button>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="rounded-xl bg-danger-soft border border-danger/30 px-4 py-3 text-sm text-danger">
            <p class="font-semibold mb-1"><i class="fa-solid fa-exclamation-triangle mr-1" aria-hidden="true"></i> Please fix the following:</p>
            <ul class="list-disc list-inside text-xs">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Tab Navigation --}}
    <div class="border-b border-border" x-data="{ activeTab: 'patient' }">
        <nav class="flex gap-1 -mb-px" aria-label="Batch entry tabs">
            <button @click="activeTab = 'patient'"
                    :class="activeTab === 'patient' ? 'border-primary text-primary' : 'border-transparent text-ink-muted hover:text-ink hover:border-border'"
                    class="px-4 py-2.5 text-sm font-semibold border-b-2 transition-colors">
                <i class="fa-solid fa-user-plus mr-1.5" aria-hidden="true"></i>
                Patient Registration
            </button>
            <button @click="activeTab = 'recent'"
                    :class="activeTab === 'recent' ? 'border-primary text-primary' : 'border-transparent text-ink-muted hover:text-ink hover:border-border'"
                    class="px-4 py-2.5 text-sm font-semibold border-b-2 transition-colors">
                <i class="fa-solid fa-clock-rotate-left mr-1.5" aria-hidden="true"></i>
                Recent Entries
                <span class="ml-1 inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold text-white" style="background: var(--primary);">{{ $recentPatients->count() }}</span>
            </button>
        </nav>
    </div>

    {{-- Tab Content --}}
    <div x-show="activeTab === 'patient'" x-cloak>
        @include('batch-entry.partials.patient-registration')
    </div>

    <div x-show="activeTab === 'recent'" x-cloak>
        @include('batch-entry.partials.recent-entries')
    </div>

</div>

<script>
function batchEntry() {
    return {
        activeTab: 'patient',
    };
}
</script>
@endsection
