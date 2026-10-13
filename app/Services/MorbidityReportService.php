<?php

namespace App\Services;

use App\Models\ApplicationSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class MorbidityReportService
{
    /**
     * FHSIS-style morbidity query for the given month/year with sex, zone and age group filters.
     * Returns ['start' => Carbon, 'rows' => Collection, 'totalCases' => int, 'reportDate' => string].
     */
    public static function query(string|int $month, string|int $year, ?string $sex, $zone, string $ageGroup, ?User $user = null): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $query = DB::table('diagnosis_records')
            ->join('consultations', 'diagnosis_records.consultation_id', '=', 'consultations.id')
            ->join('diagnosis_lookup', 'diagnosis_records.diagnosis_id', '=', 'diagnosis_lookup.id')
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->join('households', 'patients.household_id', '=', 'households.id')
            ->join('zones', 'households.zone_id', '=', 'zones.id')
            ->whereBetween('consultations.created_at', [$start, $end]);

        // Zone-assigned workers only see morbidity from their assigned zones.
        if ($user !== null && $user->isZoneScoped()) {
            $query->whereIn('households.zone_id', $user->accessibleZoneIds());
        }

        // sex filter (map shorthand to DB values)
        if ($sex && $sex !== 'All') {
            $sexMap = $sex === 'M' ? 'Male' : ($sex === 'F' ? 'Female' : $sex);
            $query->where('patients.sex', $sexMap);
        }

        // zone filter (zones select value is zone id)
        if (! empty($zone)) {
            $query->where('zones.id', $zone);
        }

        // age group filters
        switch ($ageGroup) {
            case 'infant_0_6d':
                $query->whereRaw('DATEDIFF(consultations.created_at, patients.date_of_birth) BETWEEN 0 AND 6');
                break;
            case 'infant_7_28d':
                $query->whereRaw('DATEDIFF(consultations.created_at, patients.date_of_birth) BETWEEN 7 AND 28');
                break;
            case 'infant_29_11m':
                $query->whereRaw('DATEDIFF(consultations.created_at, patients.date_of_birth) >= 29')
                    ->whereRaw('TIMESTAMPDIFF(MONTH, patients.date_of_birth, consultations.created_at) < 12');
                break;
            case 'child_1_4':
                $query->whereRaw('TIMESTAMPDIFF(YEAR, patients.date_of_birth, consultations.created_at) BETWEEN 1 AND 4');
                break;
            case 'child_5_9':
                $query->whereRaw('TIMESTAMPDIFF(YEAR, patients.date_of_birth, consultations.created_at) BETWEEN 5 AND 9');
                break;
            case 'child_10_14':
                $query->whereRaw('TIMESTAMPDIFF(YEAR, patients.date_of_birth, consultations.created_at) BETWEEN 10 AND 14');
                break;
            default:
                // adults/elderly 5-year buckets handled by specific values like '15_19', '20_24', ... or '70_plus'
                if (preg_match('/^(\d{2})_(\d{2})$/', $ageGroup, $matches)) {
                    $low = (int) $matches[1];
                    $high = (int) $matches[2];
                    $query->whereRaw("TIMESTAMPDIFF(YEAR, patients.date_of_birth, consultations.created_at) BETWEEN {$low} AND {$high}");
                } elseif ($ageGroup === '70_plus') {
                    $query->whereRaw('TIMESTAMPDIFF(YEAR, patients.date_of_birth, consultations.created_at) >= 70');
                }
                break;
        }

        $rows = $query->select(
            'diagnosis_lookup.diagnosis_code',
            'diagnosis_lookup.diagnosis_name',
            'diagnosis_lookup.category',
            DB::raw('COUNT(*) as case_count')
        )
            ->groupBy('diagnosis_lookup.id', 'diagnosis_lookup.diagnosis_code', 'diagnosis_lookup.diagnosis_name', 'diagnosis_lookup.category')
            ->orderByDesc('case_count')
            ->get();

        return [
            'start' => $start,
            'rows' => $rows,
            'totalCases' => $rows->sum('case_count'),
            'reportDate' => $start->format('F Y'),
        ];
    }

    public static function zones(?User $user = null): Collection
    {
        $query = DB::table('zones')->orderBy('zone_number');

        if ($user !== null && $user->isZoneScoped()) {
            $query->whereIn('id', $user->accessibleZoneIds());
        }

        return $query->get();
    }

    public static function zoneLabel(?string $zone): string
    {
        $zoneLabel = 'All Zones';
        if (! empty($zone)) {
            $zoneNumber = DB::table('zones')->where('id', $zone)->value('zone_number');
            $zoneLabel = $zoneNumber ? "Zone {$zoneNumber}" : 'Selected Zone';
        }

        return $zoneLabel;
    }

    public static function sexLabel(?string $sex): string
    {
        return $sex === 'M' ? 'Male' : ($sex === 'F' ? 'Female' : 'All Sex');
    }

    public static function ageGroupLabel(string $ageGroup): string
    {
        $labels = [
            'all' => 'All ages',
            'infant_0_6d' => '0–6 days',
            'infant_7_28d' => '7–28 days',
            'infant_29_11m' => '29 days – 11 months',
            'child_1_4' => '1–4 years',
            'child_5_9' => '5–9 years',
            'child_10_14' => '10–14 years',
            '70_plus' => '≥ 70 years',
        ];

        $label = $labels[$ageGroup] ?? 'All ages';
        if (! array_key_exists($ageGroup, $labels) && preg_match('/^(\d{2})_(\d{2})$/', $ageGroup, $matches)) {
            $label = "{$matches[1]}–{$matches[2]} years";
        }

        return $label;
    }

    /**
     * M2 morbidity matrix: diagnosis rows × M2 age bands × sex (Male, Female, Total).
     * Returns ['rows' => Collection, 'ageBands' => array, 'sexLabels' => array].
     *
     * @return array<string, mixed>
     */
    public static function matrix(string|int $month, string|int $year, ?string $sex, $zone, string $ageGroup, ?User $user = null): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $driver = DB::connection()->getDriverName();

        // Build base query with all filters
        $baseQuery = DB::table('diagnosis_records')
            ->join('consultations', 'diagnosis_records.consultation_id', '=', 'consultations.id')
            ->join('diagnosis_lookup', 'diagnosis_records.diagnosis_id', '=', 'diagnosis_lookup.id')
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->join('households', 'patients.household_id', '=', 'households.id')
            ->join('zones', 'households.zone_id', '=', 'zones.id')
            ->whereBetween('consultations.created_at', [$start, $end]);

        // Zone scope
        if ($user !== null && $user->isZoneScoped()) {
            $baseQuery->whereIn('households.zone_id', $user->accessibleZoneIds());
        }

        // Sex filter
        if ($sex && $sex !== 'All') {
            $sexMap = $sex === 'M' ? 'Male' : ($sex === 'F' ? 'Female' : $sex);
            $baseQuery->where('patients.sex', $sexMap);
        }

        // Zone filter
        if (! empty($zone)) {
            $baseQuery->where('zones.id', $zone);
        }

        // Age group filter (same as query())
        self::applyAgeGroupFilter($baseQuery, $ageGroup, $driver);

        // M2 age bands: 0-6d, 7-28d, 29d-11m, 1-4, 5-9, 10-14, then 5-year bands to 80+
        $ageBands = [
            '0-6d' => ['type' => 'days', 'min' => 0, 'max' => 6],
            '7-28d' => ['type' => 'days', 'min' => 7, 'max' => 28],
            '29d-11m' => ['type' => 'months', 'min' => 1, 'max' => 11],
            '1-4' => ['type' => 'years', 'min' => 1, 'max' => 4],
            '5-9' => ['type' => 'years', 'min' => 5, 'max' => 9],
            '10-14' => ['type' => 'years', 'min' => 10, 'max' => 14],
        ];
        // 5-year bands from 15-19 to 75-79, then 80+
        for ($i = 15; $i <= 75; $i += 5) {
            $ageBands["{$i}-".($i + 4)] = ['type' => 'years', 'min' => $i, 'max' => $i + 4];
        }
        $ageBands['80+'] = ['type' => 'years', 'min' => 80, 'max' => null];

        // For each diagnosis, get counts by age band and sex
        $diagnoses = (clone $baseQuery)
            ->select(
                'diagnosis_lookup.id as diagnosis_id',
                'diagnosis_lookup.diagnosis_code',
                'diagnosis_lookup.diagnosis_name',
                DB::raw('COUNT(*) as total_cases')
            )
            ->groupBy('diagnosis_lookup.id', 'diagnosis_lookup.diagnosis_code', 'diagnosis_lookup.diagnosis_name')
            ->orderByDesc('total_cases')
            ->get();

        $rows = collect();
        foreach ($diagnoses as $diag) {
            $rowData = [
                'diagnosis_id' => $diag->diagnosis_id,
                'diagnosis_code' => $diag->diagnosis_code,
                'diagnosis_name' => $diag->diagnosis_name,
                'total_cases' => (int) $diag->total_cases,
            ];

            // Get counts by age band and sex for this diagnosis
            foreach ($ageBands as $bandKey => $band) {
                $bandQuery = (clone $baseQuery)
                    ->where('diagnosis_lookup.id', $diag->diagnosis_id);

                self::applyAgeBandFilter($bandQuery, $band, $driver);

                $maleCount = (clone $bandQuery)->where('patients.sex', 'Male')->count();
                $femaleCount = (clone $bandQuery)->where('patients.sex', 'Female')->count();

                $rowData["{$bandKey}_Male"] = $maleCount;
                $rowData["{$bandKey}_Female"] = $femaleCount;
                $rowData["{$bandKey}_Total"] = $maleCount + $femaleCount;
            }

            $rows->push((object) $rowData);
        }

        return [
            'rows' => $rows,
            'ageBands' => array_keys($ageBands),
            'sexLabels' => ['Male', 'Female', 'Total'],
        ];
    }

    /**
     * Apply age group filter to query (shared with query()).
     */
    private static function applyAgeGroupFilter($query, string $ageGroup, string $driver): void
    {
        switch ($ageGroup) {
            case 'infant_0_6d':
                $query->whereRaw(self::ageDiffDays('consultations.created_at', 'patients.date_of_birth', $driver).' BETWEEN 0 AND 6');
                break;
            case 'infant_7_28d':
                $query->whereRaw(self::ageDiffDays('consultations.created_at', 'patients.date_of_birth', $driver).' BETWEEN 7 AND 28');
                break;
            case 'infant_29_11m':
                $query->whereRaw(self::ageDiffDays('consultations.created_at', 'patients.date_of_birth', $driver).' >= 29')
                    ->whereRaw(self::ageDiffMonths('consultations.created_at', 'patients.date_of_birth', $driver).' < 12');
                break;
            case 'child_1_4':
                $query->whereRaw(self::ageDiffYears('consultations.created_at', 'patients.date_of_birth', $driver).' BETWEEN 1 AND 4');
                break;
            case 'child_5_9':
                $query->whereRaw(self::ageDiffYears('consultations.created_at', 'patients.date_of_birth', $driver).' BETWEEN 5 AND 9');
                break;
            case 'child_10_14':
                $query->whereRaw(self::ageDiffYears('consultations.created_at', 'patients.date_of_birth', $driver).' BETWEEN 10 AND 14');
                break;
            default:
                if (preg_match('/^(\d{2})_(\d{2})$/', $ageGroup, $matches)) {
                    $low = (int) $matches[1];
                    $high = (int) $matches[2];
                    $query->whereRaw(self::ageDiffYears('consultations.created_at', 'patients.date_of_birth', $driver)." BETWEEN {$low} AND {$high}");
                } elseif ($ageGroup === '70_plus') {
                    $query->whereRaw(self::ageDiffYears('consultations.created_at', 'patients.date_of_birth', $driver).' >= 70');
                }
                break;
        }
    }

    /**
     * Apply age band filter to query.
     */
    private static function applyAgeBandFilter($query, array $band, string $driver): void
    {
        $type = $band['type'];
        $min = $band['min'];
        $max = $band['max'];

        if ($type === 'days') {
            $expr = self::ageDiffDays('consultations.created_at', 'patients.date_of_birth', $driver);
            if ($max !== null) {
                $query->whereRaw("{$expr} BETWEEN {$min} AND {$max}");
            } else {
                $query->whereRaw("{$expr} >= {$min}");
            }
        } elseif ($type === 'months') {
            $expr = self::ageDiffMonths('consultations.created_at', 'patients.date_of_birth', $driver);
            if ($max !== null) {
                $query->whereRaw("{$expr} BETWEEN {$min} AND {$max}");
            } else {
                $query->whereRaw("{$expr} >= {$min}");
            }
        } else { // years
            $expr = self::ageDiffYears('consultations.created_at', 'patients.date_of_birth', $driver);
            if ($max !== null) {
                $query->whereRaw("{$expr} BETWEEN {$min} AND {$max}");
            } else {
                $query->whereRaw("{$expr} >= {$min}");
            }
        }
    }

    /**
     * Get database-agnostic expression for age difference in days.
     */
    private static function ageDiffDays(string $dateCol, string $dobCol, string $driver): string
    {
        if ($driver === 'sqlite') {
            return "CAST(julianday({$dateCol}) - julianday({$dobCol}) AS INTEGER)";
        }

        return "DATEDIFF({$dateCol}, {$dobCol})";
    }

    /**
     * Get database-agnostic expression for age difference in months.
     */
    private static function ageDiffMonths(string $dateCol, string $dobCol, string $driver): string
    {
        if ($driver === 'sqlite') {
            return "CAST((julianday({$dateCol}) - julianday({$dobCol})) / 30.4375 AS INTEGER)";
        }

        return "TIMESTAMPDIFF(MONTH, {$dobCol}, {$dateCol})";
    }

    /**
     * Get database-agnostic expression for age difference in years.
     */
    private static function ageDiffYears(string $dateCol, string $dobCol, string $driver): string
    {
        if ($driver === 'sqlite') {
            return "CAST((julianday({$dateCol}) - julianday({$dobCol})) / 365.25 AS INTEGER)";
        }

        return "TIMESTAMPDIFF(YEAR, {$dobCol}, {$dateCol})";
    }

    /**
     * Leading cause sections A.2.a-g with rates per 100,000.
     *
     * @return array<string, mixed>
     */
    public static function leadingCauses(string|int $month, string|int $year, ?string $sex, $zone, ?User $user = null): array
    {
        $start = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth();

        $driver = DB::connection()->getDriverName();
        $projectedPopulation = (int) ApplicationSetting::get('projected_population', 0);

        // Build base query
        $baseQuery = DB::table('diagnosis_records')
            ->join('consultations', 'diagnosis_records.consultation_id', '=', 'consultations.id')
            ->join('diagnosis_lookup', 'diagnosis_records.diagnosis_id', '=', 'diagnosis_lookup.id')
            ->join('patients', 'consultations.patient_id', '=', 'patients.id')
            ->join('households', 'patients.household_id', '=', 'households.id')
            ->join('zones', 'households.zone_id', '=', 'zones.id')
            ->whereBetween('consultations.created_at', [$start, $end]);

        if ($user !== null && $user->isZoneScoped()) {
            $baseQuery->whereIn('households.zone_id', $user->accessibleZoneIds());
        }

        if ($sex && $sex !== 'All') {
            $sexMap = $sex === 'M' ? 'Male' : ($sex === 'F' ? 'Female' : $sex);
            $baseQuery->where('patients.sex', $sexMap);
        }

        if (! empty($zone)) {
            $baseQuery->where('zones.id', $zone);
        }

        // Get all diagnoses with case counts
        $allDiagnoses = (clone $baseQuery)
            ->select(
                'diagnosis_lookup.id as diagnosis_id',
                'diagnosis_lookup.diagnosis_code',
                'diagnosis_lookup.diagnosis_name',
                DB::raw('COUNT(*) as case_count')
            )
            ->groupBy('diagnosis_lookup.id', 'diagnosis_lookup.diagnosis_code', 'diagnosis_lookup.diagnosis_name')
            ->orderByDesc('case_count')
            ->get();

        $sections = [];

        // A.2.a All ages
        $sections['all_ages'] = [
            'label' => 'A.2.a Leading Causes of Morbidity (All Ages)',
            'rows' => $allDiagnoses->map(fn ($d) => (object) [
                'rank' => 0, // will set after slicing
                'diagnosis_code' => $d->diagnosis_code,
                'diagnosis_name' => $d->diagnosis_name,
                'case_count' => (int) $d->case_count,
            ])->values(),
        ];

        // A.2.b Males
        $maleDiagnoses = (clone $baseQuery)
            ->where('patients.sex', 'Male')
            ->select(
                'diagnosis_lookup.id as diagnosis_id',
                'diagnosis_lookup.diagnosis_code',
                'diagnosis_lookup.diagnosis_name',
                DB::raw('COUNT(*) as case_count')
            )
            ->groupBy('diagnosis_lookup.id', 'diagnosis_lookup.diagnosis_code', 'diagnosis_lookup.diagnosis_name')
            ->orderByDesc('case_count')
            ->get();
        $sections['males'] = [
            'label' => 'A.2.b Leading Causes of Morbidity (Males)',
            'rows' => $maleDiagnoses->map(fn ($d) => (object) [
                'diagnosis_code' => $d->diagnosis_code,
                'diagnosis_name' => $d->diagnosis_name,
                'case_count' => (int) $d->case_count,
            ])->values(),
        ];

        // A.2.c Females
        $femaleDiagnoses = (clone $baseQuery)
            ->where('patients.sex', 'Female')
            ->select(
                'diagnosis_lookup.id as diagnosis_id',
                'diagnosis_lookup.diagnosis_code',
                'diagnosis_lookup.diagnosis_name',
                DB::raw('COUNT(*) as case_count')
            )
            ->groupBy('diagnosis_lookup.id', 'diagnosis_lookup.diagnosis_code', 'diagnosis_lookup.diagnosis_name')
            ->orderByDesc('case_count')
            ->get();
        $sections['females'] = [
            'label' => 'A.2.c Leading Causes of Morbidity (Females)',
            'rows' => $femaleDiagnoses->map(fn ($d) => (object) [
                'diagnosis_code' => $d->diagnosis_code,
                'diagnosis_name' => $d->diagnosis_name,
                'case_count' => (int) $d->case_count,
            ])->values(),
        ];

        // A.2.d Under 5 (0-4 years)
        $under5Query = (clone $baseQuery);
        self::applyAgeGroupFilter($under5Query, 'child_1_4', $driver); // This won't work for under 5
        // Need custom filter for 0-4 years
        $under5Query = (clone $baseQuery);
        $under5Query->whereRaw(self::ageDiffYears('consultations.created_at', 'patients.date_of_birth', $driver).' < 5');
        $under5Diagnoses = (clone $under5Query)
            ->select(
                'diagnosis_lookup.id as diagnosis_id',
                'diagnosis_lookup.diagnosis_code',
                'diagnosis_lookup.diagnosis_name',
                DB::raw('COUNT(*) as case_count')
            )
            ->groupBy('diagnosis_lookup.id', 'diagnosis_lookup.diagnosis_code', 'diagnosis_lookup.diagnosis_name')
            ->orderByDesc('case_count')
            ->get();
        $sections['under_5'] = [
            'label' => 'A.2.d Leading Causes of Morbidity (Under 5 Years)',
            'rows' => $under5Diagnoses->map(fn ($d) => (object) [
                'diagnosis_code' => $d->diagnosis_code,
                'diagnosis_name' => $d->diagnosis_name,
                'case_count' => (int) $d->case_count,
            ])->values(),
        ];

        // A.2.e Adolescents 10-19
        $adolescentQuery = (clone $baseQuery);
        $adolescentQuery->whereRaw(self::ageDiffYears('consultations.created_at', 'patients.date_of_birth', $driver).' BETWEEN 10 AND 19');
        $adolescentDiagnoses = (clone $adolescentQuery)
            ->select(
                'diagnosis_lookup.id as diagnosis_id',
                'diagnosis_lookup.diagnosis_code',
                'diagnosis_lookup.diagnosis_name',
                DB::raw('COUNT(*) as case_count')
            )
            ->groupBy('diagnosis_lookup.id', 'diagnosis_lookup.diagnosis_code', 'diagnosis_lookup.diagnosis_name')
            ->orderByDesc('case_count')
            ->get();
        $sections['adolescents_10_19'] = [
            'label' => 'A.2.e Leading Causes of Morbidity (Adolescents 10-19)',
            'rows' => $adolescentDiagnoses->map(fn ($d) => (object) [
                'diagnosis_code' => $d->diagnosis_code,
                'diagnosis_name' => $d->diagnosis_name,
                'case_count' => (int) $d->case_count,
            ])->values(),
        ];

        // A.2.f Seniors 60+
        $seniorQuery = (clone $baseQuery);
        $seniorQuery->whereRaw(self::ageDiffYears('consultations.created_at', 'patients.date_of_birth', $driver).' >= 60');
        $seniorDiagnoses = (clone $seniorQuery)
            ->select(
                'diagnosis_lookup.id as diagnosis_id',
                'diagnosis_lookup.diagnosis_code',
                'diagnosis_lookup.diagnosis_name',
                DB::raw('COUNT(*) as case_count')
            )
            ->groupBy('diagnosis_lookup.id', 'diagnosis_lookup.diagnosis_code', 'diagnosis_lookup.diagnosis_name')
            ->orderByDesc('case_count')
            ->get();
        $sections['seniors_60_plus'] = [
            'label' => 'A.2.f Leading Causes of Morbidity (Seniors 60+)',
            'rows' => $seniorDiagnoses->map(fn ($d) => (object) [
                'diagnosis_code' => $d->diagnosis_code,
                'diagnosis_name' => $d->diagnosis_name,
                'case_count' => (int) $d->case_count,
            ])->values(),
        ];

        // A.2.g All ages with rate per 100,000
        $rateRows = $allDiagnoses->map(function ($d) use ($projectedPopulation) {
            $rate = $projectedPopulation > 0 ? round(((int) $d->case_count / $projectedPopulation) * 100000, 2) : 0;

            return (object) [
                'diagnosis_code' => $d->diagnosis_code,
                'diagnosis_name' => $d->diagnosis_name,
                'case_count' => (int) $d->case_count,
                'rate_per_100k' => $rate,
            ];
        })->values();
        $sections['all_ages_by_rate'] = [
            'label' => 'A.2.g Leading Causes of Morbidity (Rate per 100,000 Population)',
            'rows' => $rateRows,
        ];

        // Add rank numbers to all sections
        foreach ($sections as &$section) {
            foreach ($section['rows'] as $i => $row) {
                $row->rank = $i + 1;
            }
        }

        return $sections;
    }
}
