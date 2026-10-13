@extends('layouts.app')

@section('title', 'Edit Consultation')

@php
    $age = \Carbon\Carbon::parse($patient->date_of_birth)->age;
@endphp

@section('content')
<form action="{{ route('consultations.update', $consultation->id) }}" method="POST" id="consultationForm" class="space-y-5 lg:space-y-6">
    @csrf
    @method('PUT')

    <div class="space-y-4">
        <!-- Header Section -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="font-display font-semibold text-2xl lg:text-3xl" style="color: var(--ink);">Edit Consultation</h1>
                <p class="text-sm mt-1" style="color: var(--ink-muted);">{{ \App\Helpers\PatientCode::format((int) $consultation->patient_id) }} - {{ fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix) }}</p>
            </div>
        </div>

        <!-- Slim Horizontal Info Bar -->
        <div class="rounded-lg border px-4 py-3 flex flex-wrap gap-4 lg:gap-6 text-sm" style="background: var(--bg-surface); border-color: var(--border);">
            <div>
                <p style="color: var(--ink-muted);" class="text-xs font-medium">DATE</p>
                <p style="color: var(--ink);" class="font-medium">{{ \Carbon\Carbon::parse($consultation->created_at)->format(\App\Helpers\DateFormat::DATE_SQL.' H:i A') }}</p>
            </div>
            <div>
                <p style="color: var(--ink-muted);" class="text-xs font-medium">STATUS</p>
                <p style="color: var(--ink);" class="font-medium">{{ $consultation->status_label }}</p>
            </div>
            <div>
                <p style="color: var(--ink-muted);" class="text-xs font-medium">HEALTH WORKER</p>
                <p class="font-medium" style="color: var(--ink);">{{ $consultation->worker_name ?: 'Not assigned' }}</p>
            </div>
            <div>
                <p style="color: var(--ink-muted);" class="text-xs font-medium">NATURE OF VISIT</p>
                <p style="color: var(--ink);" class="font-medium">{{ $consultation->nature_of_visit }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-5 lg:space-y-6">

            <!-- Quick Notes - Primary Focal Point -->
            <div class="rounded-xl border p-5 lg:p-6 space-y-4" style="background: var(--bg-surface); border-color: var(--border);">
                <div>
                    <h2 class="font-semibold text-lg" style="color: var(--ink);">Quick Notes</h2>
                    <p class="text-xs mt-1" style="color: var(--ink-muted);">Diagnoses and prescriptions are managed on the consultation record.</p>
                </div>
                <textarea name="notes" placeholder="Add detailed notes about this consultation..." rows="6" class="w-full px-3 py-2 rounded-lg border text-sm focus:outline-none focus:ring-2 transition" style="border-color: var(--border); color: var(--ink); --tw-ring-color: var(--primary); resize: vertical;">{{ $consultation->notes }}</textarea>
                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="px-6 py-2 rounded-xl text-white text-sm font-semibold transition" style="background: var(--primary);">Update Consultation</button>
                    <a href="{{ route('consultations.show', $consultation->id) }}" class="px-4 py-2 rounded-xl text-sm font-semibold transition border" style="border-color: var(--border); color: var(--ink);">View Full Record</a>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-5 lg:space-y-6">
            <!-- Patient Info Card -->
            <div class="rounded-xl border p-5 lg:p-6 space-y-4" style="background: var(--bg-surface); border-color: var(--border);">
                <h3 class="font-semibold text-base" style="color: var(--ink);">Patient Information</h3>

                <div class="space-y-3 text-sm">
                    <div>
                        <p style="color: var(--ink-muted);">Name</p>
                        <p class="font-medium" style="color: var(--ink);">{{ fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix) }}</p>
                    </div>
                    <div>
                        <p style="color: var(--ink-muted);">Patient ID</p>
                        <p class="font-medium" style="color: var(--ink);">PT{{ str_pad($patient->id, 3, '0', STR_PAD_LEFT) }}</p>
                    </div>
                    <div>
                        <p style="color: var(--ink-muted);">Date of Birth</p>
                        <p class="font-medium" style="color: var(--ink);">{{ \Carbon\Carbon::parse($patient->date_of_birth)->format('Y-m-d') }} <span style="color: var(--ink-muted);">({{ $age }}y)</span></p>
                    </div>
                    <div>
                        <p style="color: var(--ink-muted);">Sex</p>
                        <p class="font-medium" style="color: var(--ink);">{{ $patient->sex }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
