{{--
    Individual Treatment Record (ITR) - BHCIS print sheet (fixed-page absolute layout)
--}}
@php
    $consultDate = $consultationAt ?? \Carbon\Carbon::parse($consultation->updated_at ?? $consultation->created_at);
    $isAm = $consultDate->format('A') === 'AM';
    $mode = $consultation->mode_of_transaction ?? '';
    $natureOfVisit = $consultation->nature_of_visit ?? '';
    $bp = ($vitals->bp_systolic ?? null) && ($vitals->bp_diastolic ?? null)
        ? $vitals->bp_systolic.'/'.$vitals->bp_diastolic
        : '';
    $temperature = $vitals->temperature_c ?? null;
    $height = $vitals->height_cm ?? null;
    $weight = $vitals->weight_kg ?? null;
    $provider = $attendingProvider ?? '';

    $diagnosisText = $diagnoses->map(function ($dx) {
        $line = $dx->diagnosis_name;
        if ($dx->diagnosis_code) {
            $line .= ' ('.$dx->diagnosis_code.')';
        }
        if ($dx->remarks) {
            $line .= ' - '.$dx->remarks;
        }

        return $line;
    })->implode("\n");

    $medicationText = $prescriptions->map(function ($rx) {
        $parts = array_filter([
            $rx->medicine_name,
            $rx->dosage,
            $rx->route,
            $rx->frequency,
            $rx->duration ? 'x '.$rx->duration : null,
            $rx->quantity ? 'Qty '.$rx->quantity : null,
            $rx->instructions,
        ]);

        return implode(' · ', $parts);
    })->implode("\n");

    $consultationTypesLeft = [
        'General', 'Prenatal', 'Dental Care', 'Child Care', 'Child Nutrition', 'Injury', 'Adult Immunization',
    ];
    $consultationTypesRight = [
        'Family Planning', 'Postpartum', 'Tuberculosis', 'Child Immunization', 'Sick Children', 'Firecracker Injury',
    ];

    $outwardReferral = $outwardReferral ?? null;
    $isReferral = $mode === 'Referral' || ($outwardReferral !== null) || (bool) ($consultation->refer_to_higher_facility ?? false);
    $providerSectionNumber = $isReferral ? 'VIII' : 'VII';
    $referredFrom = $outwardReferral?->referred_from ?? $consultation->referred_from ?? '';
    $referredTo = $outwardReferral?->destination_facility ?? $consultation->referred_to ?? 'Higher facility';
    $referralReasonText = $outwardReferral?->specific_details ?? ($consultation->referral_reason ?? '');

    $patientName = fullName($patient->last_name ?? null, $patient->first_name ?? null, $patient->middle_name ?? null, $patient->suffix ?? null);

    $meta = [
        'Patient' => $patientName,
        'Age / Sex' => trim((($age ?? null) ?? '').' '.($patient->sex ?? ''), ' '),
        'Consultation' => 'C'.str_pad((string) $consultation->id, 4, '0', STR_PAD_LEFT),
        'Date / Time' => $consultDate->format('m/d/Y g:i A'),
        'Zone' => $zoneLabel ?? '-',
    ];
@endphp

<section class="iclinic-form" aria-label="Individual Treatment Record">
    <div class="form-body">
        <div class="form-head">
            @include('consultations.handout.partials._bhcis-header', [
                'formTitle' => 'Individual Treatment Record',
                'meta' => $meta,
            ])
        </div>

        {{-- I. Patient Information --}}
        <div class="form-section" style="height:115px;">
            <table class="form-table" style="border-top:0;">
                <tr style="height:18px;">
                    <td colspan="12" class="section-header">I. Patient Information (Impormasyon ng Pasyente)</td>
                </tr>
                <tr style="height:48px;">
                    <td colspan="4">
                        <p class="field-label">Last Name <span class="field-help">(Apelyido)</span></p>
                        <p class="field-value text-bold clip-2">{{ $patient->last_name ?? '' }}</p>
                    </td>
                    <td colspan="2">
                        <p class="field-label">Suffix</p>
                        <p class="field-value clip-2">{{ $patient->suffix ?? '' }}</p>
                    </td>
                    <td colspan="4">
                        <p class="field-label">First Name <span class="field-help">(Pangalan)</span></p>
                        <p class="field-value text-bold clip-2">{{ $patient->first_name ?? '' }}</p>
                    </td>
                    <td colspan="2">
                        <p class="field-label">Middle Name <span class="field-help">(Gitnang Pangalan)</span></p>
                        <p class="field-value clip-2">{{ $patient->middle_name ?? '' }}</p>
                    </td>
                </tr>
                <tr style="height:48px;">
                    <td colspan="2">
                        <p class="field-label">Age <span class="field-help">(Edad)</span></p>
                        <p class="field-value text-bold clip-2">{{ $age ?? '' }}</p>
                    </td>
                    <td colspan="10">
                        <p class="field-label">Residential Address <span class="field-help">(Tirahan)</span></p>
                        <p class="field-value whitespace-pre clip-2">{{ $patient->residential_address ?? '' }}</p>
                    </td>
                </tr>
            </table>
        </div>

        {{-- II. Encounter --}}
        <div class="form-section" style="height:211px;">
            <table class="form-table">
                <tr style="height:18px;">
                    <td colspan="12" class="section-header">II. Encounter (Konsultasyon)</td>
                </tr>
                <tr style="height:40px;">
                    <td colspan="3">
                        <p class="field-label">Date of Consultation</p>
                        <p class="field-value clip-2">{{ $consultDate->format('m/d/Y') }} <span class="field-help">(mm/dd/yyyy)</span></p>
                    </td>
                    <td colspan="3">
                        <p class="field-label">Consultation Time</p>
                        <p class="field-value">
                            {{ $consultDate->format('g:i') }}
                            <span class="am-pm-box">
                                <span @class(['active' => $isAm])>AM</span>
                                <span class="sep">/</span>
                                <span @class(['active' => ! $isAm])>PM</span>
                            </span>
                        </p>
                    </td>
                    <td colspan="6">
                        <p class="field-label">Mode of Transaction</p>
                        @include('consultations.handout.partials._mark', ['checked' => $mode === 'Walk-in', 'label' => 'Walk-in'])
                        @include('consultations.handout.partials._mark', ['checked' => $mode === 'Visited', 'label' => 'Visited'])
                        @include('consultations.handout.partials._mark', ['checked' => $mode === 'Referral', 'label' => 'Referral'])
                    </td>
                </tr>
                <tr style="height:40px;">
                    <td colspan="12">
                        <p class="field-label">Nature of Visit</p>
                        @include('consultations.handout.partials._mark', ['checked' => in_array($natureOfVisit, ['Checkup', 'New Consultation/Case'], true), 'label' => 'New Consultation/Case'])
                        @include('consultations.handout.partials._mark', ['checked' => $natureOfVisit === 'New Admission', 'label' => 'New Admission'])
                        @include('consultations.handout.partials._mark', ['checked' => in_array($natureOfVisit, ['Follow-up', 'Follow-up Visit', 'Follow-up visit'], true), 'label' => 'Follow-up visit'])
                    </td>
                </tr>
                <tr style="height:112px;">
                    <td colspan="12">
                        <p class="field-label">Type of Consultation / Purpose of Visit</p>
                        <div class="marks-2col">
                            @foreach ($consultationTypesLeft as $type)
                                @include('consultations.handout.partials._mark', ['checked' => false, 'label' => $type, 'inline' => false])
                            @endforeach
                            @foreach ($consultationTypesRight as $type)
                                @include('consultations.handout.partials._mark', ['checked' => false, 'label' => $type, 'inline' => false])
                            @endforeach
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- III. Vitals --}}
        <div class="form-section" style="height:67px;">
            <table class="form-table">
                <tr style="height:18px;">
                    <td colspan="12" class="section-header">III. Vitals</td>
                </tr>
                <tr style="height:48px;">
                    <td colspan="3">
                        <p class="field-label">Blood Pressure</p>
                        <p class="field-value text-bold clip-2">{{ $bp }}</p>
                    </td>
                    <td colspan="3">
                        <p class="field-label">Temperature</p>
                        <p class="field-value text-bold clip-2">{{ $temperature !== null ? "{$temperature}°C" : '' }}</p>
                    </td>
                    <td colspan="3">
                        <p class="field-label">Height (cm)</p>
                        <p class="field-value text-bold clip-2">{{ $height }}</p>
                    </td>
                    <td colspan="3">
                        <p class="field-label">Weight (kg)</p>
                        <p class="field-value text-bold clip-2">{{ $weight }}</p>
                    </td>
                </tr>
            </table>
        </div>

        {{-- IV. Chief Complaint --}}
        <div class="form-section" style="height:115px;">
            <table class="form-table">
                <tr style="height:18px;">
                    <td colspan="12" class="section-header">IV. Chief Complaint (Pangunahing Reklamo)</td>
                </tr>
                <tr style="height:96px;">
                    <td colspan="12">
                        <p class="field-value whitespace-pre clip-4">{{ $consultation->complaint_text ?? '' }}</p>
                    </td>
                </tr>
            </table>
        </div>

        {{-- V. Diagnosis --}}
        <div class="form-section" style="height:107px;">
            <table class="form-table">
                <tr style="height:18px;">
                    <td colspan="12" class="section-header">V. Diagnosis (Diagnosis)</td>
                </tr>
                <tr style="height:88px;">
                    <td colspan="12">
                        <p class="field-value whitespace-pre clip-4">{{ $diagnosisText }}</p>
                    </td>
                </tr>
            </table>
        </div>

        {{-- VI. Treatment / Medication --}}
        <div class="form-section" style="height:107px;">
            <table class="form-table">
                <tr style="height:18px;">
                    <td colspan="12" class="section-header">VI. Treatment / Medication (Gamot)</td>
                </tr>
                <tr style="height:88px;">
                    <td colspan="12">
                        <p class="field-value whitespace-pre clip-4">{{ $medicationText ?: ($consultation->notes ?? '-') }}</p>
                    </td>
                </tr>
            </table>
        </div>

        {{-- VII. Referral (referral encounters only) --}}
        @if ($isReferral)
            <div class="form-section" style="height:116px;">
                <table class="form-table">
                    <tr style="height:18px;">
                        <td colspan="12" class="section-header">VII. Referral</td>
                    </tr>
                    <tr style="height:40px;">
                        <td colspan="4">
                            <p class="field-label">Referred From</p>
                            <p class="field-value clip-2">{{ $referredFrom }}</p>
                        </td>
                        <td colspan="4">
                            <p class="field-label">Referred To</p>
                            <p class="field-value clip-2">{{ $referredTo }}</p>
                        </td>
                        <td colspan="4">
                            <p class="field-label">Referred By</p>
                            <p class="field-value clip-2">{{ $provider }}</p>
                        </td>
                    </tr>
                    <tr style="height:57px;">
                        <td colspan="12">
                            <p class="field-label">Reason(s) for Referral</p>
                            <p class="field-value whitespace-pre clip-2">{{ $referralReasonText }}</p>
                        </td>
                    </tr>
                </table>
            </div>
        @endif

        {{-- Provider & Laboratory (VII without referral, VIII with) - stretches to the footer --}}
        <div class="form-section">
            <table class="form-table">
                <tr style="height:18px;">
                    <td colspan="12" class="section-header">{{ $providerSectionNumber }}. Provider &amp; Laboratory (Tagapagbigay Serbisyo at Laboratory)</td>
                </tr>
                <tr style="height:44px;">
                    <td colspan="12">
                        <p class="field-label">Name of Health Care Provider</p>
                        <p class="field-value text-bold clip-2">{{ $provider }}</p>
                    </td>
                </tr>
                <tr>
                    <td colspan="8">
                        <p class="field-label">Laboratory Findings / Impression</p>
                        <div style="min-height:120px;"></div>
                    </td>
                    <td colspan="4">
                        <p class="field-label">Performed Laboratory Test</p>
                        <div style="min-height:120px;"></div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="form-footer form-footer-flex">
        <span>BHCIS System Sta. Ana - Barangay Health Center Information System</span>
        <span>Page 1</span>
        <span>Printed {{ now()->format('m/d/Y g:i A') }}</span>
    </div>
</section>
