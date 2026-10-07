{{--
    Individual Treatment Record (ITR 2) - Family Planning / Prenatal / Immunization / Postpartum
    BHCIS print sheet (fixed-page absolute layout)
--}}
@php
    $pregnancy = $pregnancy ?? null;
    $prenatalVisits = collect($prenatalVisits ?? [])->sortBy('visit_date')->values();
    $postnatalRecord = $postnatalRecord ?? null;
    $fpClient = $fpClient ?? null;
    $immunizations = collect($immunizations ?? []);
    $lastVisit = $prenatalVisits->last();

    $fmtDate = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('m/d/Y') : '';

    $immDate = function (array $codes, string $needle) use ($immunizations) {
        foreach ($immunizations as $imm) {
            $code = strtolower($imm->vaccine?->vaccine_code ?? '');
            if ($code !== '' && in_array($code, $codes, true)) {
                return $imm->date_given;
            }
        }
        $needle = strtolower($needle);
        foreach ($immunizations as $imm) {
            $name = strtolower($imm->vaccine?->vaccine_name ?? '');
            if ($needle !== '' && $name !== '' && str_contains($name, $needle)) {
                return $imm->date_given;
            }
        }

        return null;
    };

    $childVaccineRows = [
        ['Hepa B with 24 hrs.', ['hepab'], 'hepatitis b'],
        ['Hepa B ≥ 24 hrs', [], ''],
        ['PENTA 1', ['penta1'], 'pentavalent 1'],
        ['PENTA 2', ['penta2'], 'pentavalent 2'],
        ['PENTA 3', ['penta3'], 'pentavalent 3'],
        ['OPV 1', ['opv1'], 'oral polio vaccine 1'],
        ['OPV 2', ['opv2'], 'oral polio vaccine 2'],
        ['OPV 3', ['opv3'], 'oral polio vaccine 3'],
        ['MCV 1 (AMV)', ['mcv1'], 'measles containing vaccine 1'],
        ['MCV-2 (MMR)', ['mcv2'], 'measles containing vaccine 2'],
        ['ROTA 1', ['rota1'], 'rotavirus 1'],
        ['ROTA 2', ['rota2'], 'rotavirus 2'],
        ['PCV 1', ['pcv1'], 'pneumococcal 1'],
        ['PCV 2', ['pcv2'], 'pneumococcal 2'],
        ['PCV 3', ['pcv3'], 'pneumococcal 3'],
        ['Hepa B2', ['hepa b2'], 'hepatitis b 2'],
        ['Hepa B3', ['hepa b3'], 'hepatitis b 3'],
        ['Hepa A', ['hepa a'], 'hepatitis a'],
        ['Pneumonia', ['pneumonia'], 'pneumonia'],
        ['Influenza', [], 'influenza'],
        ['Others:', [], ''],
    ];

    $adultVaccineRows = [
        ['Pneumococcal', ['pneumococcal'], 'pneumococcal'],
        ['Flu', [], 'flu'],
        ['Others:', [], ''],
    ];

    $syphilis = strtolower($pregnancy->syphilis_result ?? '');
    $syphilisNegative = in_array($syphilis, ['negative', 'non-reactive', 'non_reactive'], true);
    $syphilisPositive = str_contains($syphilis, 'pos') || in_array($syphilis, ['reactive'], true);
    $penicillin = strtolower($pregnancy->penicillin ?? '');

    $fpType = \App\Models\FamilyPlanningClient::TYPES[$fpClient->type_of_client ?? ''] ?? ($fpClient->type_of_client ?? '');
    $outcome = \App\Models\PostnatalRecord::OUTCOMES[$postnatalRecord->pregnancy_outcome ?? ''] ?? ($postnatalRecord->pregnancy_outcome ?? '');
    $placeDelivered = \App\Models\PostnatalRecord::PLACES[$postnatalRecord->place_delivered ?? ''] ?? ($postnatalRecord->place_delivered ?? '');
    $modeOfDelivery = \App\Models\PostnatalRecord::MODES[$postnatalRecord->mode_of_delivery ?? ''] ?? ($postnatalRecord->mode_of_delivery ?? '');
    $attendantAtBirth = \App\Models\PostnatalRecord::ATTENDANTS[$postnatalRecord->attendant_at_birth ?? ''] ?? ($postnatalRecord->attendant_at_birth ?? '');
    $dangerSignsMother = implode(', ', $postnatalRecord->danger_signs_mother ?? []);
    $dangerSignsBaby = implode(', ', $postnatalRecord->danger_signs_baby ?? []);

    $patientName = fullName($patient->last_name ?? null, $patient->first_name ?? null, $patient->middle_name ?? null, $patient->suffix ?? null);

    $meta = [
        'Patient' => $patientName,
        'Age / Sex' => trim((($age ?? null) ?? '').' '.($patient->sex ?? ''), ' '),
        'Consultation' => 'C'.str_pad((string) $consultation->id, 4, '0', STR_PAD_LEFT),
        'Date' => ($consultationAt ?? \Carbon\Carbon::parse($consultation->updated_at ?? $consultation->created_at))->format('m/d/Y'),
        'Zone' => $zoneLabel ?? '-',
    ];
@endphp

<section class="iclinic-form" aria-label="Individual Treatment Record 2">
    <div class="form-body">
        <div class="form-head">
            @include('consultations.handout.partials._bhcis-header', [
                'formTitle' => 'Individual Treatment Record (ITR 2)',
                'meta' => $meta,
            ])
        </div>

        {{-- Family Planning + Prenatal + Immunization columns --}}
        <div class="form-section" style="height:616px;">
            <table class="form-table" style="border-top:0;">
                <tr>
                    {{-- Left column: Family Planning + Prenatal --}}
                    <td colspan="7" style="padding:0;">
                        <table class="form-table nested-table" style="border:0;">
                            <tr style="height:18px;">
                                <td colspan="8" class="section-header">Family Planning</td>
                            </tr>
                            <tr style="height:30px;">
                                <td colspan="3" class="label-cell">Type of Client</td>
                                <td colspan="5"><div class="clip clip-2">{{ $fpType }}</div></td>
                            </tr>
                            <tr style="height:30px;">
                                <td colspan="3" class="label-cell">Method</td>
                                <td colspan="5"><div class="clip clip-2">{{ $fpClient->method ?? '' }}</div></td>
                            </tr>
                            <tr style="height:30px;">
                                <td colspan="3" class="label-cell">If Drop-Out, state reason</td>
                                <td colspan="5"><div class="clip clip-2">{{ $fpClient->drop_out_reason ?? '' }}</div></td>
                            </tr>
                            <tr style="height:26px;">
                                <td colspan="3" class="label-cell">Schedule of Next Visit</td>
                                <td colspan="5">{{ $fmtDate($fpClient->schedule_next_visit ?? null) }}</td>
                            </tr>

                            <tr style="height:18px;">
                                <td colspan="8" class="section-header">Prenatal</td>
                            </tr>
                            <tr style="height:28px;">
                                <td colspan="2" class="label-cell">Gravidity</td>
                                <td colspan="2">{{ $pregnancy->gravidity ?? '' }}</td>
                                <td colspan="2" class="label-cell">LMP</td>
                                <td colspan="2">{{ $fmtDate($pregnancy->lmp ?? null) }}</td>
                            </tr>
                            <tr style="height:28px;">
                                <td colspan="2" class="label-cell">Parity</td>
                                <td colspan="2">{{ $pregnancy->parity ?? '' }}</td>
                                <td colspan="2" class="label-cell">EDC</td>
                                <td colspan="2">{{ $fmtDate($pregnancy->edc ?? null) }}</td>
                            </tr>
                            <tr style="height:28px;">
                                <td colspan="2" class="label-cell">Term</td>
                                <td colspan="2">{{ $pregnancy->term ?? '' }}</td>
                                <td colspan="2" class="label-cell">AOG</td>
                                <td colspan="2">{{ $pregnancy->aog_weeks ?? '' }}</td>
                            </tr>
                            <tr style="height:28px;">
                                <td colspan="2" class="label-cell">Preterm</td>
                                <td colspan="2">{{ $pregnancy->preterm ?? '' }}</td>
                                <td colspan="2" class="label-cell">TT</td>
                                <td colspan="2">{{ $fmtDate($pregnancy->tt_date ?? null) }}</td>
                            </tr>
                            <tr style="height:28px;">
                                <td colspan="2" class="label-cell">Livebirth</td>
                                <td colspan="2">{{ $pregnancy->livebirth ?? '' }}</td>
                                <td colspan="2" class="label-cell">Iron</td>
                                <td colspan="2">{{ $pregnancy ? (($pregnancy->iron_taken ?? false) ? 'Yes' : 'No') : '' }}</td>
                            </tr>
                            <tr style="height:28px;">
                                <td colspan="2" class="label-cell">Abortion</td>
                                <td colspan="2">{{ $pregnancy->abortion ?? '' }}</td>
                                <td colspan="2" class="label-cell">Others</td>
                                <td colspan="2"><div class="clip clip-2">{{ $pregnancy->others ?? '' }}</div></td>
                            </tr>
                            <tr style="height:30px;">
                                <td colspan="2" class="label-cell">Syphilis Result</td>
                                <td colspan="3">
                                    @include('consultations.handout.partials._mark', ['checked' => $syphilisNegative, 'label' => 'Negative'])
                                </td>
                                <td colspan="3">
                                    @include('consultations.handout.partials._mark', ['checked' => $syphilisPositive, 'label' => 'Positive'])
                                </td>
                            </tr>
                            <tr style="height:30px;">
                                <td colspan="2" class="label-cell">Penicillin</td>
                                <td colspan="3">
                                    @include('consultations.handout.partials._mark', ['checked' => $penicillin === 'no', 'label' => 'No'])
                                </td>
                                <td colspan="3">
                                    @include('consultations.handout.partials._mark', ['checked' => $penicillin === 'yes', 'label' => 'Yes'])
                                </td>
                            </tr>
                            <tr style="height:26px;">
                                <td colspan="3" class="label-cell">Schedule of Next Visit</td>
                                <td colspan="5">{{ $fmtDate($lastVisit->next_visit_date ?? null) }}</td>
                            </tr>
                            <tr style="height:26px;">
                                <td colspan="3" class="label-cell">Fundic Height (cm)</td>
                                <td colspan="5">{{ $lastVisit->fundic_height_cm ?? '' }}</td>
                            </tr>
                            <tr style="height:26px;">
                                <td colspan="3" class="label-cell">Fetal Heart Tone</td>
                                <td colspan="5">{{ $lastVisit->fetal_heart_tone_bpm ?? '' }}</td>
                            </tr>

                            <tr style="height:18px;">
                                <td colspan="8" class="section-header">Prenatal Visits</td>
                            </tr>
                            <tr style="height:20px;">
                                @for ($i = 0; $i < 8; $i++)
                                    <td>{{ $fmtDate($prenatalVisits[$i]->visit_date ?? null) }}</td>
                                @endfor
                            </tr>
                            <tr style="height:20px;">
                                @for ($i = 0; $i < 8; $i++)
                                    <td>&nbsp;</td>
                                @endfor
                            </tr>

                            <tr style="height:18px;">
                                <td colspan="8" class="section-header">Menstrual History</td>
                            </tr>
                            <tr style="height:26px;">
                                <td colspan="2" class="label-cell">Menarche</td>
                                <td colspan="2"></td>
                                <td colspan="2" class="label-cell">Onset of sexual intercourse</td>
                                <td colspan="2"></td>
                            </tr>
                            <tr style="height:26px;">
                                <td colspan="2" class="label-cell">Period / Duration</td>
                                <td colspan="2"></td>
                                <td colspan="2" class="label-cell">Birth Control Method</td>
                                <td colspan="2"></td>
                            </tr>
                            <tr style="height:26px;">
                                <td colspan="2" class="label-cell">Interval / Cycle</td>
                                <td colspan="2"></td>
                                <td colspan="2" class="label-cell">Menopause? (Yes/No)</td>
                                <td colspan="2"></td>
                            </tr>
                        </table>
                    </td>

                    {{-- Right column: Immunization --}}
                    <td colspan="5" style="padding:0;">
                        <table class="form-table nested-table" style="border:0;">
                            <tr style="height:18px;">
                                <td colspan="2" class="section-header">Child Immunization</td>
                            </tr>
                            <tr style="height:26px;">
                                <td class="label-cell" style="width:55%;">Birth Weight</td>
                                <td></td>
                            </tr>
                            <tr style="height:18px;">
                                <td class="label-cell text-center">Immunization</td>
                                <td class="label-cell text-center">Date Given</td>
                            </tr>
                            @foreach ($childVaccineRows as [$rowLabel, $rowCodes, $rowNeedle])
                                <tr style="height:20px;">
                                    <td>{{ $rowLabel }}</td>
                                    <td>{{ $fmtDate($immDate($rowCodes, $rowNeedle)) }}</td>
                                </tr>
                            @endforeach

                            <tr style="height:18px;">
                                <td colspan="2" class="section-header">Adult Immunization</td>
                            </tr>
                            <tr style="height:18px;">
                                <td class="label-cell text-center">Immunization</td>
                                <td class="label-cell text-center">Date Given</td>
                            </tr>
                            @foreach ($adultVaccineRows as [$rowLabel, $rowCodes, $rowNeedle])
                                <tr style="height:20px;">
                                    <td>{{ $rowLabel }}</td>
                                    <td>{{ $fmtDate($immDate($rowCodes, $rowNeedle)) }}</td>
                                </tr>
                            @endforeach
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Postpartum - stretches to the footer --}}
        <div class="form-section">
            <table class="form-table">
                <tr style="height:20px;">
                    <td colspan="12" class="section-header">Postpartum (Pagkatapos ng Panganak)</td>
                </tr>
                <tr style="height:30px;">
                    <td colspan="6" class="label-cell text-center">Delivery</td>
                    <td colspan="6" class="label-cell text-center">Child Information</td>
                </tr>
                <tr style="height:36px;">
                    <td colspan="3" class="label-cell">Prenatal Outcome</td>
                    <td colspan="3"><div class="clip clip-2">{{ $outcome }}</div></td>
                    <td colspan="3" class="label-cell">Last Name</td>
                    <td colspan="3"><div class="field-value text-bold clip-2">{{ $postnatalRecord->child_last_name ?? '' }}</div></td>
                </tr>
                <tr style="height:36px;">
                    <td colspan="3" class="label-cell">Prenatal Delivered</td>
                    <td colspan="3"></td>
                    <td colspan="3" class="label-cell">First Name</td>
                    <td colspan="3"><div class="field-value text-bold clip-2">{{ $postnatalRecord->child_first_name ?? '' }}</div></td>
                </tr>
                <tr style="height:36px;">
                    <td colspan="3" class="label-cell">Place Delivered</td>
                    <td colspan="3"><div class="clip clip-2">{{ $placeDelivered }}</div></td>
                    <td colspan="3" class="label-cell">Middle Name</td>
                    <td colspan="3"><div class="clip clip-2">{{ $postnatalRecord->child_middle_name ?? '' }}</div></td>
                </tr>
                <tr style="height:36px;">
                    <td colspan="3" class="label-cell">Mode of Delivery</td>
                    <td colspan="3"><div class="clip clip-2">{{ $modeOfDelivery }}</div></td>
                    <td colspan="3" class="label-cell">Sex (M / F)</td>
                    <td colspan="3">{{ $postnatalRecord->child_sex ?? '' }}</td>
                </tr>
                <tr style="height:36px;">
                    <td colspan="3" class="label-cell">Attendant at Birth</td>
                    <td colspan="3"><div class="clip clip-2">{{ $attendantAtBirth }}</div></td>
                    <td colspan="3" class="label-cell">Birth Length (cm)</td>
                    <td colspan="3">{{ $postnatalRecord->child_birth_length_cm ?? '' }}</td>
                </tr>
                <tr style="height:36px;">
                    <td colspan="3" class="label-cell">Delivery Date</td>
                    <td colspan="3">{{ $fmtDate($postnatalRecord->delivery_date ?? null) }}</td>
                    <td colspan="3" class="label-cell">Birth Weight (kg)</td>
                    <td colspan="3">{{ $postnatalRecord->child_birth_weight_kg ?? '' }}</td>
                </tr>
                <tr style="height:36px;">
                    <td colspan="3" class="label-cell">Delivery Time</td>
                    <td colspan="3">{{ $postnatalRecord->delivery_time ?? '' }}</td>
                    <td colspan="3" class="label-cell">Breastfeeding Initiated</td>
                    <td colspan="3">
                        {{ $fmtDate($postnatalRecord->breastfeeding_date ?? null) }}
                        {{ $postnatalRecord->breastfeeding_time ?? '' }}
                    </td>
                </tr>
                <tr style="height:48px;">
                    <td colspan="6" class="label-cell-sm">Postpartum visit within 24 hrs after delivery</td>
                    <td colspan="2">{{ $fmtDate($postnatalRecord->postpartum_24h_date ?? null) }}</td>
                    <td colspan="2" class="label-cell-sm">Danger Signs (Mother)</td>
                    <td colspan="2"><div class="field-value-sm clip clip-2">{{ $dangerSignsMother }}</div></td>
                </tr>
                <tr style="height:48px;">
                    <td colspan="6" class="label-cell-sm">Postpartum visit within 1 week after delivery</td>
                    <td colspan="2">{{ $fmtDate($postnatalRecord->postpartum_7d_date ?? null) }}</td>
                    <td colspan="2" class="label-cell-sm">Danger Signs (Baby)</td>
                    <td colspan="2"><div class="field-value-sm clip clip-2">{{ $dangerSignsBaby }}</div></td>
                </tr>
                <tr style="height:36px;">
                    <td colspan="3" class="label-cell">Date Vitamin A Given</td>
                    <td colspan="3">{{ $fmtDate($postnatalRecord->vitamin_a_date ?? null) }}</td>
                    <td colspan="3" class="label-cell">Iron Given (date / count)</td>
                    <td colspan="3">
                        {{ $fmtDate($postnatalRecord->iron_date ?? null) }}
                        {{ ($postnatalRecord->iron_count ?? null) !== null ? '· x'.$postnatalRecord->iron_count : '' }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="form-footer form-footer-flex">
        <span>BHCIS System Sta. Ana - Barangay Health Center Information System</span>
        <span>Page 2</span>
        <span>Printed {{ now()->format('m/d/Y g:i A') }}</span>
    </div>
</section>
