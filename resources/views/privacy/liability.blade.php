@extends('layouts.public')

@section('title', 'Liability & Disclaimer')

@section('content')
<div class="flex items-start justify-between gap-4 mb-6 sticky top-0 z-20 py-3 -mx-4 px-4" style="background: var(--bg-page);">
    <div class="flex items-start gap-3">
        <span class="mt-0.5 header-chip">
            <i class="fa-solid fa-scale-balanced text-lg" aria-hidden="true"></i>
        </span>
        <div>
            <h1 class="font-display font-semibold text-2xl lg:text-3xl text-ink">Liability &amp; Disclaimer</h1>
            <p class="text-sm mt-1 text-ink-muted">Barangay Health Center Information System - Sta. Ana</p>
        </div>
    </div>
    <a href="{{ auth()->check() ? route('dashboard') : route('login') }}"
       class="shrink-0 inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-white transition hover:opacity-90"
       style="background: var(--primary); box-shadow: 0 4px 14px -2px rgba(13, 74, 60, 0.4);">
        <i class="fa-solid fa-arrow-left text-xs"></i>
        Back to System
    </a>
</div>

<x-card class="mt-6">
    <div class="prose max-w-none" style="color: var(--ink); font-size: 1rem; line-height: 1.8;">

        <h3 style="font-size: 1.15rem; font-weight: 700; margin-top: 2rem; margin-bottom: 0.75rem;">System Disclaimer</h3>
        <p style="margin-bottom: 1rem;">The Barangay Health Center Information System (BHCIS) is a clinical support and record-keeping tool designed to assist healthcare workers in managing patient data, immunization records, maternal care tracking, and consultation histories.</p>
        <p style="margin-bottom: 1rem;">It does not replace the professional medical judgment of licensed healthcare providers.</p>
        <p style="margin-bottom: 1rem;">All clinical decisions remain the sole responsibility of the attending health professional. The system, its developers, and the Barangay Sta. Ana Health Center shall not be held liable for any clinical outcomes resulting from the use of information stored in this system.</p>

        <h3 style="font-size: 1.15rem; font-weight: 700; margin-top: 2rem; margin-bottom: 0.75rem;">Limitation of Liability</h3>
        <p style="margin-bottom: 1rem;">To the maximum extent permitted by applicable law, the developers and operators of BHCIS shall not be liable for any direct, indirect, incidental, special, or consequential damages arising out of or in connection with the use of this system, including but not limited to:</p>
        <ul style="margin-bottom: 1rem; margin-top: 0.5rem; padding-left: 1.25rem;">
            <li style="margin-bottom: 0.25rem;">Errors or omissions in recorded patient data</li>
            <li style="margin-bottom: 0.25rem;">System downtime or data loss</li>
            <li style="margin-bottom: 0.25rem;">Clinical decisions made based on system-generated information</li>
            <li>Unauthorized access to patient records despite reasonable security measures</li>
        </ul>

        <h3 style="font-size: 1.15rem; font-weight: 700; margin-top: 2rem; margin-bottom: 0.75rem;">Data Privacy (RA 10173)</h3>
        <p style="margin-bottom: 1rem;">In accordance with Republic Act No. 10173 (Data Privacy Act of 2012), patient data collected through this system is processed for primary healthcare delivery, maternal and child health monitoring, immunization record-keeping, and DOH compliance reporting.</p>
        <p style="margin-bottom: 1rem;">For data privacy concerns, contact the Barangay Health Center.</p>

        <h3 style="font-size: 1.15rem; font-weight: 700; margin-top: 2rem; margin-bottom: 0.75rem;">Academic Project Notice</h3>
        <p style="margin-bottom: 1rem;">This system is a capstone project developed by PHINMA COC students for academic purposes.</p>
        <p>It is not an official system of the Department of Health (DOH), the Rural Health Unit (RHU), or any government agency. The system is intended for demonstration and educational use within the context of Barangay Sta. Ana Health Center operations.</p>

    </div>
</x-card>
@endsection
