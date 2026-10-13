<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maternal, EPI & Family Planning Report - {{ $reportDate }}</title>
    <style>
        @page {
            size: landscape;
            margin: 10mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 8px;
            line-height: 1.2;
            color: #333;
            margin: 0;
            padding: 5px;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 2px solid #333;
            padding-bottom: 6px;
        }
        .header h1 {
            font-size: 14px;
            font-weight: bold;
            margin: 0;
        }
        .header p {
            margin: 2px 0;
            font-size: 8px;
        }
        .report-info {
            text-align: center;
            margin-bottom: 10px;
            font-size: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 7px;
        }
        th, td {
            border: 1px solid #333;
            padding: 2px;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .signatures { margin-top: 20px; display: flex; justify-content: space-between; }
        .signature-line { width: 45%; text-align: center; }
        .signature-line hr { border: none; border-top: 1px solid #333; margin: 20px 0 3px 0; }
        .signature-title { font-size: 8px; font-weight: bold; }
        .no-data { text-align: center; padding: 10px; font-style: italic; color: #666; font-size: 8px; }
        h2 { font-size: 10px; margin: 8px 0 4px 0; font-weight: bold; }
        h3 { font-size: 9px; margin: 6px 0 3px 0; font-weight: bold; }
        .summary-block { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 6px; }
        .summary-block table { margin-bottom: 0; min-width: 200px; flex: 1; }
        .section-label { font-size: 8px; font-weight: bold; margin: 4px 0 2px 0; }
    </style>
</head>
<body>
    @include('reports.partials._fhsis-header', [
        'formCode' => 'M1',
        'reportDate' => $reportDate,
        'projectedPopulation' => \App\Models\ApplicationSetting::get('projected_population'),
        'mode' => 'pdf',
    ])

    <div class="report-info">
        <h2 style="font-size: 10px; margin: 0;">Monthly Maternal, EPI & Family Planning Report</h2>
        <p style="margin: 2px 0;">Report Period: {{ $reportDate }} | Zone: {{ $zoneLabel }} | Program: {{ $programLabel }}</p>
    </div>

    @php
        $maternal = $summaries['maternal'] ?? null;
        $epi = $summaries['epi'] ?? null;
        $fp = $summaries['fp'] ?? null;
    @endphp

    @if ($maternal !== null)
        <h2>Maternal Care</h2>
        <div class="summary-block">
            <table>
                <thead>
                    <tr>
                        <th>Indicator</th>
                        <th style="width: 50px;" class="text-center">Count</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>New prenatal clients registered</td><td class="text-right">{{ number_format($maternal['newPrenatalClients']) }}</td></tr>
                    <tr><td>Prenatal visits conducted</td><td class="text-right">{{ number_format($maternal['prenatalVisits']) }}</td></tr>
                    <tr><td>Pregnant women with 4+ prenatal visits</td><td class="text-right">{{ number_format($maternal['prenatalFourPlus']) }}</td></tr>
                    <tr><td>TT doses</td><td class="text-right">{{ number_format($maternal['ttDoses']) }}</td></tr>
                    <tr><td>Iron supplementation</td><td class="text-right">{{ number_format($maternal['ironSupplemented']) }}</td></tr>
                    <tr><td>Syphilis screening - reactive</td><td class="text-right">{{ number_format($maternal['syphilisPositive']) }}</td></tr>
                    <tr><td>Deliveries</td><td class="text-right">{{ number_format($maternal['totalDeliveries']) }}</td></tr>
                    <tr><td>Postnatal within 24 hours</td><td class="text-right">{{ number_format($maternal['postpartum24h']) }}</td></tr>
                    <tr><td>Postnatal within 7 days</td><td class="text-right">{{ number_format($maternal['postpartum7d']) }}</td></tr>
                    <tr><td>Postnatal within 14 days</td><td class="text-right">{{ number_format($maternal['postpartum14d']) }}</td></tr>
                    <tr><td>Postnatal within 28 days</td><td class="text-right">{{ number_format($maternal['postpartum28d']) }}</td></tr>
                </tbody>
            </table>

            <table>
                <thead>
                    <tr>
                        <th>Place of Delivery</th>
                        <th style="width: 50px;" class="text-center">Count</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($maternal['deliveriesByPlace'] as $row)
                        <tr><td>{{ \App\Services\MchEpiFpReportService::placeLabel($row->key) }}</td><td class="text-right">{{ number_format($row->total) }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-center no-data">No deliveries recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <table>
                <thead>
                    <tr>
                        <th>Deliveries by Attendant</th>
                        <th style="width: 50px;" class="text-center">Count</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($maternal['deliveriesByAttendant'] as $row)
                        <tr><td>{{ \App\Services\MchEpiFpReportService::attendantLabel((string) $row->key) }}</td><td class="text-right">{{ number_format($row->total) }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-center no-data">No deliveries recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <table>
                <thead>
                    <tr>
                        <th>Outcome</th>
                        <th style="width: 50px;" class="text-center">Count</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($maternal['deliveriesByOutcome'] as $row)
                        <tr><td>{{ \App\Services\MchEpiFpReportService::outcomeLabel($row->key) }}</td><td class="text-right">{{ number_format($row->total) }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="text-center no-data">No deliveries recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    @if ($epi !== null)
        <h2>EPI Immunization</h2>
        <div class="summary-block">
            <table>
                <thead>
                    <tr>
                        <th>Indicator</th>
                        <th style="width: 50px;" class="text-center">Count</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Child doses</td><td class="text-right">{{ number_format($epi['childDoses']) }}</td></tr>
                    <tr><td>Adult doses</td><td class="text-right">{{ number_format($epi['adultDoses']) }}</td></tr>
                    <tr><td>Total doses given</td><td class="text-right">{{ number_format($epi['totalDoses']) }}</td></tr>
                    <tr><td>Fully Immunized Children (FIC)</td><td class="text-right">{{ number_format($epi['fic'] ?? 0) }}</td></tr>
                    <tr><td>Child Protected at Birth (CPAB)</td><td class="text-right">{{ number_format($epi['cpab'] ?? 0) }}</td></tr>
                </tbody>
            </table>

            <table>
                <thead>
                    <tr>
                        <th>Doses by Sex</th>
                        <th style="width: 50px;" class="text-center">Count</th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td>Male</td><td class="text-right">{{ number_format($epi['dosesBySex']['Male'] ?? 0) }}</td></tr>
                    <tr><td>Female</td><td class="text-right">{{ number_format($epi['dosesBySex']['Female'] ?? 0) }}</td></tr>
                    <tr><td>Total</td><td class="text-right">{{ number_format($epi['dosesBySex']['Total'] ?? 0) }}</td></tr>
                </tbody>
            </table>

            <table>
                <thead>
                    <tr>
                        <th>Antigen & Dose</th>
                        <th style="width: 80px;" class="text-center">Doses Administered</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($epi['dosesByVaccine'] as $row)
                        <tr>
                            <td>{{ $row->vaccine_name }} {{ $row->dose_number }}</td>
                            <td class="text-right">{{ number_format($row->doses) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-center no-data">No doses recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    @if ($fp !== null)
        <h2>Family Planning</h2>
        <table>
            <thead>
                <tr>
                    <th>Method</th>
                    <th class="text-center">New</th>
                    <th class="text-center">Continuing</th>
                    <th class="text-center">Drop Outs</th>
                    <th class="text-center">Others</th>
                    <th class="text-center">Visits</th>
                    <th class="text-center">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($fp['rows'] as $row)
                    <tr>
                        <td>{{ $row->method }}</td>
                        <td class="text-right">{{ number_format($row->new_acceptors) }}</td>
                        <td class="text-right">{{ number_format($row->continuing_users) }}</td>
                        <td class="text-right">{{ number_format($row->drop_outs) }}</td>
                        <td class="text-right">{{ number_format($row->others) }}</td>
                        <td class="text-right">{{ number_format($row->visits) }}</td>
                        <td class="text-right">{{ number_format($row->total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center no-data">No family planning clients for this period.</td></tr>
                @endforelse
            </tbody>
        </table>

        <table>
            <thead>
                <tr>
                    <th>Clients by Sex</th>
                    <th style="width: 50px;" class="text-center">Count</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>Male</td><td class="text-right">{{ number_format($fp['clientsBySex']['Male'] ?? 0) }}</td></tr>
                <tr><td>Female</td><td class="text-right">{{ number_format($fp['clientsBySex']['Female'] ?? 0) }}</td></tr>
                <tr><td>Total</td><td class="text-right">{{ number_format($fp['clientsBySex']['Total'] ?? 0) }}</td></tr>
            </tbody>
        </table>
    @endif

    @if (!empty($summaries['natality']))
        @php $natality = $summaries['natality']; @endphp
        <h2>Natality (M1 Section I)</h2>
        <table>
            <thead>
                <tr>
                    <th>Indicator</th>
                    <th style="width: 50px;" class="text-center">Value</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>Live births</td><td class="text-right">{{ number_format($natality['liveBirths'] ?? 0) }}</td></tr>
                <tr><td>Crude Birth Rate (per 1,000)</td><td class="text-right">{{ number_format($natality['cbr'] ?? 0, 2) }}</td></tr>
            </tbody>
        </table>

        <table>
            <thead>
                <tr>
                    <th>Live Births by Mother Age Band</th>
                    <th style="width: 50px;" class="text-center">Count</th>
                </tr>
            </thead>
            <tbody>
                <tr><td>10-14</td><td class="text-right">{{ number_format($natality['byMotherAge']['10-14'] ?? 0) }}</td></tr>
                <tr><td>15-19</td><td class="text-right">{{ number_format($natality['byMotherAge']['15-19'] ?? 0) }}</td></tr>
                <tr><td>20-49</td><td class="text-right">{{ number_format($natality['byMotherAge']['20-49'] ?? 0) }}</td></tr>
            </tbody>
        </table>
    @endif

    <h2>Service Register ({{ number_format($totalRows) }} records)</h2>

    @php
        $registerPrograms = [
            \App\Services\MchEpiFpReportService::PROGRAM_MATERNAL,
            \App\Services\MchEpiFpReportService::PROGRAM_FP,
            \App\Services\MchEpiFpReportService::PROGRAM_EPI,
        ];
        $rowsByProgram = $rows->groupBy('program');
    @endphp

    @foreach ($registerPrograms as $registerProgram)
        @if ($summaries[$registerProgram] === null)
            @continue
        @endif

        @php
            $programRows = $rowsByProgram->get($registerProgram, collect());
            $programLabel = \App\Services\MchEpiFpReportService::PROGRAMS[$registerProgram];
        @endphp

        <h3>{{ $programLabel }} ({{ number_format($programRows->count()) }} records)</h3>
        <table>
            <thead>
                <tr>
                    <th style="width: 60px;">Date</th>
                    <th>Service</th>
                    <th>Patient</th>
                    <th style="width: 40px;">Zone</th>
                    <th>Health Worker</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($programRows as $row)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($row->date)->format('m/d/Y') }}</td>
                        <td>{{ $row->service }}</td>
                        <td>{{ $row->patient_name }} <span style="color: #666;">({{ $row->patient_code }})</span></td>
                        <td class="text-center">{{ $row->zone }}</td>
                        <td>{{ $row->worker_name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center no-data">No records for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach

    <div class="signatures">
        <div class="signature-line">
            <div class="signature-title">Prepared By:</div>
            <hr>
            <p style="font-size: 7px; margin: 3px 0;">Midwife / Barangay Health Worker</p>
        </div>
        <div class="signature-line">
            <div class="signature-title">Noted By:</div>
            <hr>
            <p style="font-size: 7px; margin: 3px 0;">Health Center In-Charge</p>
        </div>
    </div>
</body>
</html>