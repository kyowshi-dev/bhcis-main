@extends('layouts.app')

@section('title', 'Batch Data Entry')

@section('content')
<div class="max-w-5xl mx-auto space-y-3 lg:space-y-4" x-data="batchEntry()">

    <x-flash dismissible />

    <x-page-header
        title="Batch Data Entry"
        subtitle="Digitize paper forms from BHW field visits. Enter patient data from paper records into the system."
        icon="fa-solid fa-file-medical"
    >
        <x-slot name="actions">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium" style="background: var(--teal-soft); color: var(--primary);">
                <i class="fa-solid fa-file-medical" aria-hidden="true"></i>
                Paper-to-Digital Mode
            </span>
        </x-slot>
    </x-page-header>
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
                <span class="ml-1 inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold text-white" style="background: var(--primary);" x-ref="recentBadge" x-text="0"></span>
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
        recentCount: 0,

        init() {
            // Listen for patient-added from registration form
            window.addEventListener('patient-added', () => {
                this.recentCount++;
            });
            // Listen for count updates from recent-entries component
            this.$on('recent-entries-updated', (e) => {
                this.recentCount = e.detail.count;
            });
        },
    };
}
</script>
@endsection
