@extends('layouts.app')

@section('title', 'Death Record')

@section('content')
<div class="space-y-5 lg:space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Death Record</h1>
            <p class="text-sm mt-1" style="color: var(--ink-muted);">
                {{ fullName($death->patient->last_name, $death->patient->first_name, $death->patient->middle_name) }}
                ({{ $death->patient->patient_code }})
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('deaths.edit', $death) }}" class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-border text-xs lg:text-sm font-medium text-ink transition hover:bg-black/5">Edit</a>
            <x-destructive-form
                action="{{ route('deaths.destroy', $death) }}"
                message="Delete this death record? This cannot be undone."
                confirmText="Delete"
            >
                <button type="submit" class="inline-flex items-center justify-center px-4 py-2 rounded-xl transition" style="background: var(--danger-soft); color: var(--danger);">
                    <i class="fa-solid fa-trash mr-1.5" aria-hidden="true"></i> Delete
                </button>
            </x-destructive-form>
            <a href="{{ route('patients.show', $death->patient_id) }}" class="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-border text-xs lg:text-sm font-medium text-ink transition hover:bg-black/5">Back to Patient</a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
        <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
            <h2 class="font-display font-semibold text-sm mb-4" style="color: var(--ink);">Death Details</h2>
            <dl class="space-y-3 text-xs lg:text-sm">
                <div class="flex justify-between gap-3">
                    <dt style="color: var(--ink-muted);">Date of Death</dt>
                    <dd class="font-semibold" style="color: var(--ink);">{{ $death->date_of_death->format('M d, Y') }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt style="color: var(--ink-muted);">Place of Death</dt>
                    <dd class="font-semibold" style="color: var(--ink);">{{ $death->place_of_death }}</dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt style="color: var(--ink-muted);">Cause (ICD)</dt>
                    <dd class="font-semibold" style="color: var(--ink);">
                        @if ($death->causeDiagnosis)
                            {{ $death->causeDiagnosis->diagnosis_code }} - {{ $death->causeDiagnosis->diagnosis_name }}
                        @else
                            <span class="text-ink-muted">Not specified</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt style="color: var(--ink-muted);">Cause (Free Text)</dt>
                    <dd class="font-semibold" style="color: var(--ink);">
                        @if ($death->cause_text)
                            {{ $death->cause_text }}
                        @else
                            <span class="text-ink-muted">Not specified</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt style="color: var(--ink-muted);">Recorded By</dt>
                    <dd class="font-semibold" style="color: var(--ink);">
                        @if ($death->recordedBy)
                            {{ $death->recordedBy->first_name }} {{ $death->recordedBy->last_name }}
                        @else
                            <span class="text-ink-muted">Unknown</span>
                        @endif
                    </dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt style="color: var(--ink-muted);">Recorded At</dt>
                    <dd class="font-semibold" style="color: var(--ink);">{{ $death->created_at->format('M d, Y H:i') }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
            <h2 class="font-display font-semibold text-sm mb-4" style="color: var(--ink);">Classification</h2>
            <dl class="space-y-3 text-xs lg:text-sm">
                <div class="flex justify-between gap-3">
                    <dt style="color: var(--ink-muted);">Maternal Death</dt>
                    <dd class="font-semibold" style="color: {{ $death->maternal_death ? 'var(--danger)' : 'var(--ink-muted)' }};">
                        {{ $death->maternal_death ? 'Yes' : 'No' }}
                    </dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt style="color: var(--ink-muted);">Perinatal Death</dt>
                    <dd class="font-semibold" style="color: {{ $death->perinatal_death ? 'var(--danger)' : 'var(--ink-muted)' }};">
                        {{ $death->perinatal_death ? 'Yes' : 'No' }}
                    </dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt style="color: var(--ink-muted);">Neonatal Death</dt>
                    <dd class="font-semibold" style="color: {{ $death->neonatal_death ? 'var(--danger)' : 'var(--ink-muted)' }};">
                        {{ $death->neonatal_death ? 'Yes' : 'No' }}
                    </dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt style="color: var(--ink-muted);">Infant Death</dt>
                    <dd class="font-semibold" style="color: {{ $death->infant_death ? 'var(--danger)' : 'var(--ink-muted)' }};">
                        {{ $death->infant_death ? 'Yes' : 'No' }}
                    </dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt style="color: var(--ink-muted);">Under-5 Death</dt>
                    <dd class="font-semibold" style="color: {{ $death->under_five_death ? 'var(--danger)' : 'var(--ink-muted)' }};">
                        {{ $death->under_five_death ? 'Yes' : 'No' }}
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
        <h2 class="font-display font-semibold text-sm mb-4" style="color: var(--ink);">Patient Information</h2>
        <dl class="space-y-2 text-xs lg:text-sm">
            <div class="flex justify-between gap-3">
                <dt style="color: var(--ink-muted);">Name</dt>
                <dd class="font-semibold" style="color: var(--ink);">{{ fullName($death->patient->last_name, $death->patient->first_name, $death->patient->middle_name) }}</dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt style="color: var(--ink-muted);">Patient Code</dt>
                <dd class="font-semibold" style="color: var(--ink);">{{ $death->patient->patient_code }}</dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt style="color: var(--ink-muted);">Age at Death</dt>
                <dd class="font-semibold" style="color: var(--ink);">{{ $death->patient->age_at_death($death->date_of_death) ?? 'N/A' }}</dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt style="color: var(--ink-muted);">Sex</dt>
                <dd class="font-semibold" style="color: var(--ink);">{{ $death->patient->sex }}</dd>
            </div>
            <div class="flex justify-between gap-3">
                <dt style="color: var(--ink-muted);">Zone</dt>
                <dd class="font-semibold" style="color: var(--ink);">Zone {{ $death->patient->zone_id }}</dd>
            </div>
        </dl>
    </div>

@if ($death->cause_text)
    <div class="rounded-xl border p-4 lg:p-5" style="background: var(--bg-surface); border-color: var(--border);">
        <h2 class="font-display font-semibold text-sm mb-3" style="color: var(--ink);">Cause of Death (Free Text)</h2>
        <p class="text-sm" style="color: var(--ink); white-space: pre-wrap;">{{ $death->cause_text }}</p>
    </div>
@endif
</div>
@endsection