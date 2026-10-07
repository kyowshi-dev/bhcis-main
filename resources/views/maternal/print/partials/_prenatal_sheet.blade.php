{{--
    Prenatal Record - print sheet (fixed-page absolute layout)
    Expects: $pregnancy (Pregnancy, loaded with patient.household.zone, visits)
--}}
@php
    $patient = $pregnancy->patient;
    $patientName = fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix);
    $aog = $pregnancy->aog_weeks
        ?? ($pregnancy->edc !== null ? max(0, \Carbon\Carbon::today()->diffInWeeks(\Carbon\Carbon::parse($pregnancy->edc)->subDays(280))) : null);
    $visits = $pregnancy->visits->sortByDesc('visit_date')->values();
    $visitSlots = max(17, $visits->count());
@endphp

<section class="iclinic-form" aria-label="Prenatal Record">
    <div class="form-body">
        <div class="form-head">
            @include('consultations.handout.partials._bhcis-header', [
                'formTitle' => 'Prenatal Record',
                'meta' => [
                    'Patient' => $patientName,
                    'Age / Sex' => trim($patient->age.' '.($patient->sex ?? ''), ' '),
                    'Zone' => 'Zone '.($patient->household?->zone?->zone_number ?? $patient->household?->zone_id ?? '-'),
                    'Registered' => $pregnancy->created_at->format('m/d/Y'),
                ],
            ])
        </div>

        {{-- I. Patient Information --}}
        <div class="form-section" style="height:131px;">
            <table class="form-table" style="border-top:0;">
                <tr style="height:18px;">
                    <td colspan="8" class="section-header">I. Patient Information</td>
                </tr>
                <tr style="height:56px;">
                    <td colspan="4">
                        <p class="field-label">Last Name <span class="field-help">(Apelyido)</span></p>
                        <p class="field-value text-bold clip-2">{{ $patient->last_name }}</p>
                    </td>
                    <td colspan="3">
                        <p class="field-label">First Name <span class="field-help">(Pangalan)</span></p>
                        <p class="field-value text-bold clip-2">{{ $patient->first_name }}</p>
                    </td>
                    <td>
                        <p class="field-label">Suffix</p>
                        <p class="field-value clip-2">{{ $patient->suffix ?? '' }}</p>
                    </td>
                </tr>
                <tr style="height:56px;">
                    <td>
                        <p class="field-label">Age</p>
                        <p class="field-value clip-2">{{ $patient->age }}</p>
                    </td>
                    <td>
                        <p class="field-label">Sex</p>
                        <p class="field-value clip-2">{{ $patient->sex }}</p>
                    </td>
                    <td colspan="2">
                        <p class="field-label">Civil Status</p>
                        <p class="field-value clip-2">{{ $patient->civil_status }}</p>
                    </td>
                    <td colspan="2">
                        <p class="field-label">Zone / Purok</p>
                        <p class="field-value clip-2">Zone {{ $patient->household?->zone?->zone_number ?? $patient->household?->zone_id ?? '-' }}</p>
                    </td>
                    <td colspan="2">
                        <p class="field-label">Spouse / Partner</p>
                        <p class="field-value clip-2">{{ $patient->spouse_name ?? '' }}</p>
                    </td>
                </tr>
            </table>
        </div>

        {{-- II. Pregnancy --}}
        <div class="form-section" style="height:259px;">
            <table class="form-table">
                <tr style="height:18px;">
                    <td colspan="8" class="section-header">II. Pregnancy</td>
                </tr>
                <tr class="label-cell-sm text-center" style="height:20px;">
                    <td>Gravida</td>
                    <td>Para</td>
                    <td>Term</td>
                    <td>Preterm</td>
                    <td>Live Births</td>
                    <td>Abortions</td>
                    <td colspan="2">Status</td>
                </tr>
                <tr class="text-center" style="height:44px;">
                    <td class="text-bold">{{ $pregnancy->gravidity }}</td>
                    <td class="text-bold">{{ $pregnancy->parity }}</td>
                    <td>{{ $pregnancy->term }}</td>
                    <td>{{ $pregnancy->preterm }}</td>
                    <td>{{ $pregnancy->livebirth }}</td>
                    <td>{{ $pregnancy->abortion }}</td>
                    <td colspan="2" class="text-bold text-upper">{{ $pregnancy->status }}</td>
                </tr>
                <tr style="height:44px;">
                    <td colspan="2" class="label-cell">LMP</td>
                    <td colspan="2"><p class="field-value text-bold clip-1">{{ $pregnancy->lmp?->format('M d, Y') ?? '' }}</p></td>
                    <td colspan="2" class="label-cell">EDC (LMP + 280 days)</td>
                    <td colspan="2"><p class="field-value text-bold clip-1">{{ $pregnancy->edc?->format('M d, Y') ?? '' }}</p></td>
                </tr>
                <tr style="height:44px;">
                    <td colspan="2" class="label-cell">AOG</td>
                    <td colspan="2"><p class="field-value text-bold clip-1">{{ $aog !== null ? $aog.' weeks' : '' }}</p></td>
                    <td colspan="2" class="label-cell">Syphilis (RPR)</td>
                    <td colspan="2"><p class="field-value text-bold clip-1">{{ ucfirst($pregnancy->syphilis_result) }}</p></td>
                </tr>
                <tr style="height:44px;">
                    <td colspan="2" class="label-cell">Penicillin</td>
                    <td colspan="2"><p class="field-value clip-1">{{ ucfirst($pregnancy->penicillin) }}</p></td>
                    <td colspan="2" class="label-cell">TT Dose Date</td>
                    <td colspan="2"><p class="field-value clip-1">{{ $pregnancy->tt_date?->format('M d, Y') ?? '' }}</p></td>
                </tr>
                <tr style="height:44px;">
                    <td colspan="2" class="label-cell">Iron Taken</td>
                    <td colspan="2"><p class="field-value clip-1">{{ $pregnancy->iron_taken ? 'Yes' : 'No' }}</p></td>
                    <td colspan="2" class="label-cell">Others</td>
                    <td colspan="2"><p class="field-value clip-2">{{ $pregnancy->others ?? '' }}</p></td>
                </tr>
            </table>
        </div>

        {{-- III. Prenatal Visits - stretches to the footer --}}
        <div class="form-section">
            <table class="form-table">
                <tr style="height:18px;">
                    <td colspan="8" class="section-header">III. Prenatal Visits</td>
                </tr>
                <tr class="label-cell-sm text-center" style="height:20px;">
                    <td style="width:16%;">Visit Date</td>
                    <td style="width:18%;">Fundic Height (cm)</td>
                    <td style="width:18%;">Fetal Heart Tone (bpm)</td>
                    <td style="width:18%;">Next Visit</td>
                    <td style="width:30%;">Remarks</td>
                </tr>
                @for ($i = 0; $i < $visitSlots; $i++)
                    <tr style="height:34px;" @class(['text-center' => isset($visits[$i])])>
                        <td>{{ $visits->get($i)?->visit_date->format('M d, Y') ?? '' }}</td>
                        <td>{{ $visits->get($i)?->fundic_height_cm ?? '' }}</td>
                        <td>{{ $visits->get($i)?->fetal_heart_tone_bpm ?? '' }}</td>
                        <td>{{ $visits->get($i)?->next_visit_date?->format('M d, Y') ?? '' }}</td>
                        <td></td>
                    </tr>
                @endfor
                <tr>
                    <td colspan="5">&nbsp;</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="form-footer form-footer-flex">
        <span>BHCIS System Sta. Ana - Barangay Health Center Information System</span>
        <span>Registered: {{ $pregnancy->created_at->format('M d, Y g:i A') }}</span>
    </div>
</section>
