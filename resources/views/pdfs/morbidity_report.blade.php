<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Month Morbidity Disease Report - {{ $reportDate }}</title>
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
        .total-row { background-color: #f5f5f5; font-weight: bold; }
        .signatures { margin-top: 20px; display: flex; justify-content: space-between; }
        .signature-line { width: 45%; text-align: center; }
        .signature-line hr { border: none; border-top: 1px solid #333; margin: 20px 0 3px 0; }
        .signature-title { font-size: 8px; font-weight: bold; }
        .no-data { text-align: center; padding: 10px; font-style: italic; color: #666; font-size: 8px; }
        h2 { font-size: 10px; margin: 8px 0 4px 0; font-weight: bold; }
        h3 { font-size: 9px; margin: 6px 0 3px 0; font-weight: bold; }
        .section-label { font-size: 8px; font-weight: bold; margin: 4px 0 2px 0; }
    </style>
</head>
<body>
    @include('reports.partials._fhsis-header', [
        'formCode' => 'M2',
        'reportDate' => $reportDate,
        'projectedPopulation' => \App\Models\ApplicationSetting::get('projected_population'),
        'mode' => 'pdf',
    ])

    <div class="report-info">
        <h2 style="font-size: 10px; margin: 0;">Monthly Morbidity Disease Report</h2>
        <p style="margin: 2px 0;">Leading Causes of Morbidity (M2 Matrix)</p>
        <p style="margin: 2px 0;">Report Period: {{ $reportDate }}</p>
        <p style="margin: 2px 0; font-weight: bold;">Selected filters: Sex: {{ $sexLabel }} | Zone: {{ $zoneLabel }} | Age group: {{ $ageGroupLabel }}</p>
    </div>

    @php
        $ageBands = $matrix['ageBands'] ?? [];
        $sexLabels = $matrix['sexLabels'] ?? ['Male', 'Female', 'Total'];
        $matrixRows = $matrix['rows'] ?? collect();
        $leadingCauses = $leadingCauses ?? [];

        // Group age bands into categories for separate tables
        $ageGroups = [
            'Infants' => ['0-6d', '7-28d', '29d-11m'],
            'Children 1-14' => ['1-4', '5-9', '10-14'],
            'Adolescents 15-19' => ['15-19'],
            'Adults 20-49' => ['20-24', '25-29', '30-34', '35-39', '40-44', '45-49'],
            'Adults 50-64' => ['50-54', '55-59', '60-64'],
            'Elderly 65+' => ['65-69', '70-74', '75-79', '80+'],
        ];
    @endphp

    {{-- M2 Matrix Tables by Age Group --}}
    @foreach ($ageGroups as $groupName => $bands)
        @php
            $groupBands = array_intersect($ageBands, $bands);
        @endphp
        @if (!empty($groupBands))
            <div class="mb-3" style="page-break-inside: avoid;">
                <h3>M2 Matrix: {{ $groupName }}</h3>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 90px;">Diagnosis</th>
                                <th style="width: 45px;">ICD</th>
                                <th style="width: 30px; text-align: center;">Total</th>
                                @foreach ($groupBands as $band)
                                    @foreach ($sexLabels as $sexLabel)
                                        <th style="width: 28px; text-align: center; font-size: 6px;">{{ $band }}<br>{{ $sexLabel }}</th>
                                    @endforeach
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($matrixRows as $row)
                                <tr>
                                    <td>{{ $row->diagnosis_name }}</td>
                                    <td>{{ $row->diagnosis_code }}</td>
                                    <td class="text-center font-weight-bold">{{ number_format($row->total_cases) }}</td>
                                    @foreach ($groupBands as $band)
                                        @foreach ($sexLabels as $sexLabel)
                                            <td class="text-center">{{ number_format($row->{"{$band}_{$sexLabel}"} ?? 0) }}</td>
                                        @endforeach
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ 3 + count($groupBands) * count($sexLabels) }}" class="text-center no-data">No morbidity data for this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endforeach

    {{-- Leading Causes Sections A.2.a-g --}}
    <div style="page-break-before: auto;">
        @foreach (['all_ages', 'males', 'females', 'under_5', 'adolescents_10_19', 'seniors_60_plus', 'all_ages_by_rate'] as $sectionKey)
            @if (isset($leadingCauses[$sectionKey]))
                @php
                    $section = $leadingCauses[$sectionKey];
                    $rows = $section['rows'] ?? collect();
                    $isRateSection = $sectionKey === 'all_ages_by_rate';
                @endphp
                <div style="margin-bottom: 8px; page-break-inside: avoid;">
                    <div class="section-label">{{ $section['label'] }}</div>
                    <table style="width: 100%;">
                        <thead>
                            <tr>
                                <th style="width: 25px; text-align: center;">Rank</th>
                                <th style="width: 40px; text-align: center;">ICD</th>
                                <th>Diagnosis</th>
                                <th style="width: 35px; text-align: right;">Cases</th>
                                @if ($isRateSection)
                                    <th style="width: 50px; text-align: right;">Rate/100k</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows->take(10) as $row)
                                <tr>
                                    <td class="text-center">{{ $row->rank ?? $loop->iteration }}</td>
                                    <td class="text-center">{{ $row->diagnosis_code }}</td>
                                    <td>{{ $row->diagnosis_name }}</td>
                                    <td class="text-right">{{ number_format($row->case_count) }}</td>
                                    @if ($isRateSection)
                                        <td class="text-right">{{ number_format($row->rate_per_100k ?? 0, 2) }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endforeach
    </div>

    {{-- Original Ranked Table --}}
    @if($rows->isNotEmpty())
    <div style="margin-top: 10px;">
        <div class="section-label">Ranked Leading Causes (All Ages)</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 30px; text-align: center;">Rank</th>
                    <th style="width: 60px;">ICD Code</th>
                    <th>Diagnosis / Cause</th>
                    <th style="width: 50px; text-align: right;">Cases</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $rank => $row)
                    <tr>
                        <td class="text-center">{{ $rank + 1 }}</td>
                        <td>{{ $row->diagnosis_code }}</td>
                        <td>{{ $row->diagnosis_name }}</td>
                        <td class="text-right">{{ number_format($row->case_count) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3" style="text-align: right; font-weight: bold;">Total Cases:</td>
                    <td class="text-right">{{ number_format($totalCases) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <div class="signatures">
        <div class="signature-line">
            <div class="signature-title">Prepared By:</div>
            <hr>
            <p style="font-size: 7px; margin: 3px 0;">Barangay Health Worker</p>
        </div>
        <div class="signature-line">
            <div class="signature-title">Approved By:</div>
            <hr>
            <p style="font-size: 7px; margin: 3px 0;">Barangay Captain / Health Center In-Charge</p>
        </div>
    </div>
</body>
</html>