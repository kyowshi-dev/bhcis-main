{{--
    Family Planning Client Record - print sheet (fixed-page absolute layout)
    Expects: $client (FamilyPlanningClient, loaded with patient.household.zone, visits)
--}}
@php
    $patient = $client->patient;
    $patientName = fullName($patient->last_name, $patient->first_name, $patient->middle_name, $patient->suffix);
    $visits = $client->visits->sortByDesc('visit_date')->values();
    $visitSlots = max(16, $visits->count());
@endphp

<section class="iclinic-form" aria-label="Family Planning Client Record">
    <div class="form-body">
        <div class="form-head">
            @include('consultations.handout.partials._bhcis-header', [
                'formTitle' => 'Family Planning Client Record',
                'meta' => [
                    'Patient' => $patientName,
                    'Age / Sex' => trim($patient->age.' '.($patient->sex ?? ''), ' '),
                    'Zone' => 'Zone '.($patient->household?->zone?->zone_number ?? $patient->household?->zone_id ?? '-'),
                    'Recorded' => $client->created_at->format('m/d/Y'),
                ],
            ])
        </div>

        {{-- I. Client Information --}}
        <div class="form-section" style="height:179px;">
            <table class="form-table" style="border-top:0;">
                <tr style="height:18px;">
                    <td colspan="8" class="section-header">I. Client Information</td>
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
                    <td>
                        <p class="field-label">Birthdate</p>
                        <p class="field-value clip-2">{{ $patient->date_of_birth?->format('m/d/Y') }}</p>
                    </td>
                    <td colspan="2">
                        <p class="field-label">Civil Status</p>
                        <p class="field-value clip-2">{{ $patient->civil_status }}</p>
                    </td>
                    <td colspan="3">
                        <p class="field-label">Zone / Purok</p>
                        <p class="field-value clip-2">Zone {{ $patient->household?->zone?->zone_number ?? $patient->household?->zone_id ?? '-' }}</p>
                    </td>
                </tr>
                <tr style="height:48px;">
                    <td colspan="8">
                        <p class="field-label">Residential Address <span class="field-help">(Tirahan)</span></p>
                        <p class="field-value clip-2">{{ $patient->residential_address }}</p>
                    </td>
                </tr>
            </table>
        </div>

        {{-- II. Registration --}}
        <div class="form-section" style="height:235px;">
            <table class="form-table">
                <tr style="height:18px;">
                    <td colspan="8" class="section-header">II. Registration</td>
                </tr>
                <tr style="height:56px;">
                    <td colspan="4" class="label-cell">Type of Client</td>
                    <td colspan="4">
                        <p class="mark-block">
                            <span class="mark-box">{{ in_array($client->type_of_client, ['new_acceptor', 'drop_out'], true) ? 'X' : '' }}</span>
                            <span class="mark-label">{{ \App\Models\FamilyPlanningClient::TYPES['new_acceptor'] }} (New Acceptor)</span>
                        </p>
                        <p class="mark-block">
                            <span class="mark-box">{{ in_array($client->type_of_client, ['continuing_user', 'drop_out'], true) ? 'X' : '' }}</span>
                            <span class="mark-label">{{ \App\Models\FamilyPlanningClient::TYPES['continuing_user'] }} (Continuing User)</span>
                        </p>
                        <p class="mark-block">
                            <span class="mark-box">{{ $client->type_of_client === 'drop_out' ? 'X' : '' }}</span>
                            <span class="mark-label">{{ \App\Models\FamilyPlanningClient::TYPES['drop_out'] }} (Drop Out)</span>
                        </p>
                        <p class="mark-block">
                            <span class="mark-box">{{ $client->type_of_client === 'others' ? 'X' : '' }}</span>
                            <span class="mark-label">{{ \App\Models\FamilyPlanningClient::TYPES['others'] }} (Others)</span>
                        </p>
                    </td>
                </tr>
                <tr style="height:40px;">
                    <td colspan="4" class="label-cell">Method Used</td>
                    <td colspan="4"><p class="field-value text-bold clip-2">{{ $client->method }}</p></td>
                </tr>
                <tr style="height:40px;">
                    <td colspan="4" class="label-cell">Drop-Out Reason</td>
                    <td colspan="4"><p class="field-value clip-2">{{ $client->drop_out_reason ?? '' }}</p></td>
                </tr>
                <tr style="height:40px;">
                    <td colspan="4" class="label-cell">Next Follow-up</td>
                    <td colspan="4"><p class="field-value text-bold clip-1">{{ $client->schedule_next_visit?->format('M d, Y') ?? '' }}</p></td>
                </tr>
                <tr style="height:40px;">
                    <td colspan="4" class="label-cell">Status</td>
                    <td colspan="4">
                        <p class="mark-block">
                            <span class="mark-box">{{ $client->is_active ? 'X' : '' }}</span>
                            <span class="mark-label">Active</span>
                            <span style="display:inline-block;width:24px;"></span>
                            <span class="mark-box">{{ ! $client->is_active ? 'X' : '' }}</span>
                            <span class="mark-label">Inactive</span>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        {{-- III. Follow-up Visits - stretches to the footer --}}
        <div class="form-section">
            <table class="form-table">
                <tr style="height:18px;">
                    <td colspan="4" class="section-header">III. Follow-up Visits</td>
                </tr>
                <tr class="label-cell-sm" style="height:20px;">
                    <td style="width:18%;">Date</td>
                    <td style="width:24%;">Method</td>
                    <td style="width:26%;">Next Follow-up</td>
                    <td style="width:32%;">Remarks</td>
                </tr>
                @for ($i = 0; $i < $visitSlots; $i++)
                    <tr style="height:34px;">
                        <td>{{ $visits->get($i)?->visit_date->format('M d, Y') ?? '' }}</td>
                        <td>{{ $visits->get($i)?->method ?? '' }}</td>
                        <td>{{ $visits->get($i)?->schedule_next_visit?->format('M d, Y') ?? '' }}</td>
                        <td></td>
                    </tr>
                @endfor
                <tr>
                    <td colspan="4">&nbsp;</td>
                </tr>
            </table>
        </div>
    </div>

    <div class="form-footer form-footer-flex">
        <span>BHCIS System Sta. Ana - Barangay Health Center Information System</span>
        <span>Recorded: {{ $client->created_at->format('M d, Y g:i A') }}</span>
    </div>
</section>
