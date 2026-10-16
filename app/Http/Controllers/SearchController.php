<?php

namespace App\Http\Controllers;

use App\Helpers\PatientCode;
use App\Services\FuzzySearchService;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    /**
     * Zone scoping helper for patient queries.
     *
     * Archived (soft-deleted) patients are always excluded - they are not
     * viewable via patients.show and only appear on the index archived tab.
     *
     * Zone-scoped users (BHWs) may search patients within their assigned
     * zones, plus any patient they enrolled themselves (even when that
     * patient's household lies outside their zones).
     */
    private function scopePatientQuery(Builder $query): Builder
    {
        $query->whereNull('deleted_at');

        $user = auth()->user();

        if ($user !== null && $user->isZoneScoped()) {
            $query->where(function (Builder $q) use ($user) {
                $q->whereIn('household_id', $user->accessibleHouseholdIds())
                    ->orWhere('enrolled_by', $user->id);
            });
        }

        return $query;
    }

    /**
     * Search for Patients (by Name)
     *
     * Fast indexed prefix match first; when that cannot fill the result
     * list, a fuzzy (Levenshtein) re-rank over a bounded candidate pool
     * (first-letter + substring matches) tolerates spelling errors.
     */
    public function patients(Request $request)
    {
        $query = $request->input('query');

        if (empty($query)) {
            return response()->json([]);
        }

        $limit = 10;

        $prefixResults = $this->scopePatientQuery(DB::table('patients'))
            ->where(function ($qb) use ($query) {
                $qb->where('last_name', 'LIKE', "{$query}%")
                    ->orWhere('first_name', 'LIKE', "{$query}%");
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->distinct()
            ->select('id', 'first_name', 'last_name', 'sex', 'date_of_birth')
            ->limit($limit)
            ->get();

        if ($prefixResults->count() < $limit) {
            $prefixResults = $prefixResults->merge(
                $this->fuzzyPatientCandidates($query, $prefixResults, $limit)
            );
        }

        return response()->json($this->formatPatients($prefixResults->take($limit)));
    }

    /**
     * Build a bounded candidate pool (first-letter and substring matches)
     * and re-rank it by edit distance against the query. Prefix matches
     * already returned are excluded.
     *
     * @param  Collection<int, \stdClass>  $alreadyReturned
     * @return Collection<int, \stdClass>
     */
    private function fuzzyPatientCandidates(string $query, $alreadyReturned, int $limit)
    {
        $firstChar = mb_substr($query, 0, 1);

        if ($firstChar === '') {
            return collect();
        }

        $candidates = $this->scopePatientQuery(DB::table('patients'))
            ->where(function ($qb) use ($query, $firstChar) {
                $qb->where('last_name', 'LIKE', "{$firstChar}%")
                    ->orWhere('first_name', 'LIKE', "{$firstChar}%")
                    ->orWhere('last_name', 'LIKE', "%{$query}%")
                    ->orWhere('first_name', 'LIKE', "%{$query}%");
            })
            ->whereNotIn('id', $alreadyReturned->pluck('id'))
            ->distinct()
            ->select('id', 'first_name', 'last_name', 'sex', 'date_of_birth')
            ->limit(500)
            ->get();

        $fuzzy = app(FuzzySearchService::class)->rank(
            $candidates,
            $query,
            fn ($p): string => trim((string) $p->last_name).' '.trim((string) $p->first_name),
            $limit
        );

        return collect($fuzzy);
    }

    /**
     * Format patient rows for the autocomplete dropdown.
     *
     * @param  Collection<int, \stdClass>  $patients
     * @return array<int, array<string, mixed>>
     */
    private function formatPatients($patients): array
    {
        $patientIds = $patients->pluck('id')->toArray();

        $activePregnancyIds = DB::table('pregnancies')
            ->whereIn('patient_id', $patientIds)
            ->where('status', 'active')
            ->pluck('patient_id')
            ->toArray();

        return $patients->map(function ($patient) use ($activePregnancyIds) {
            $ptCode = PatientCode::format((int) $patient->id);
            $age = null;
            if (! empty($patient->date_of_birth)) {
                $age = Carbon::parse($patient->date_of_birth)->age;
            }

            return [
                'id' => $patient->id,
                'text' => trim((string) $patient->last_name).', '.trim((string) $patient->first_name), // What shows in the dropdown
                'subtext' => $ptCode.' | '.trim((string) $patient->sex).($age !== null ? ' | '.$age.' y/o' : '').' | '.$patient->date_of_birth, // Extra info
                'first_name' => trim((string) $patient->first_name),
                'last_name' => trim((string) $patient->last_name),
                'sex' => trim((string) $patient->sex),
                'date_of_birth' => $patient->date_of_birth,
                'has_active_pregnancy' => in_array($patient->id, $activePregnancyIds, true),
            ];
        })->all();
    }

    /**
     * Search for Diagnosis (ICD-10 or Name)
     */
    public function diagnoses(Request $request)
    {
        $query = $request->input('query');

        if (empty($query)) {
            return response()->json([]);
        }

        return response()->json($this->localDiagnoses($query));
    }

    /**
     * Local diagnosis fallback. Codes are matched strictly (prefix/
     * substring) - never fuzzed, since a wrong code suggestion is a
     * clinical safety issue. Names get a fuzzy re-rank when the strict
     * pass cannot fill the result list.
     *
     * @return array<int, array{id: int, text: string}>
     */
    private function localDiagnoses(string $query): array
    {
        $limit = 15;

        $diagnoses = DB::table('diagnosis_lookup')
            ->where('diagnosis_name', 'LIKE', "%{$query}%")
            ->orWhere('diagnosis_code', 'LIKE', "{$query}%")
            ->select('id', 'diagnosis_code', 'diagnosis_name')
            ->limit($limit)
            ->get();

        if ($diagnoses->count() < $limit) {
            $firstChar = mb_substr($query, 0, 1);

            if ($firstChar !== '') {
                $candidates = DB::table('diagnosis_lookup')
                    ->where('diagnosis_name', 'LIKE', "{$firstChar}%")
                    ->whereNotIn('id', $diagnoses->pluck('id'))
                    ->select('id', 'diagnosis_code', 'diagnosis_name')
                    ->limit(300)
                    ->get();

                $fuzzy = app(FuzzySearchService::class)->rank(
                    $candidates,
                    $query,
                    fn ($d): string => (string) $d->diagnosis_name,
                    $limit - $diagnoses->count()
                );

                $diagnoses = $diagnoses->concat($fuzzy);
            }
        }

        return $diagnoses->map(function ($d) {
            return [
                'id' => (int) $d->id,
                'text' => $d->diagnosis_code ? ($d->diagnosis_code.' - '.$d->diagnosis_name) : $d->diagnosis_name,
            ];
        })->all();
    }

    /**
     * Search for Medicines (Generic Name)
     */
    public function medicines(Request $request)
    {
        $query = $request->input('query');

        if (empty($query)) {
            return response()->json([]);
        }

        $medicines = DB::table('medicines_lookup')
            ->whereNull('deleted_at')
            ->where('name', 'LIKE', "%{$query}%")
            ->select('id', 'name', 'form')
            ->limit(15)
            ->get();

        $results = $medicines->map(function ($m) {
            return [
                'id' => $m->id,
                'text' => $m->name,
                'form' => $m->form,
            ];
        });

        return response()->json($results);
    }

    /**
     * Search for Households (by head name / Zone / Contact)
     *
     * Strict substring pass first; fuzzy re-rank on the head's full name
     * fills the list when the strict pass comes up short.
     *
     * Not zone-scoped: patient enrollment may attach a patient to any
     * household in the barangay, so zone-scoped workers get the full list.
     */
    public function households(Request $request)
    {
        $query = $request->input('query');

        if (empty($query)) {
            return response()->json([]);
        }

        $limit = 15;

        $households = DB::table('households')
            ->join('zones', 'households.zone_id', '=', 'zones.id')
            ->where(function ($qb) use ($query) {
                $qb->where('households.head_first_name', 'LIKE', "%{$query}%")
                    ->orWhere('households.head_last_name', 'LIKE', "%{$query}%")
                    ->orWhere('zones.zone_number', 'LIKE', "%{$query}%")
                    ->orWhere('households.contact_number', 'LIKE', "%{$query}%");
            })
            ->distinct()
            ->select('households.id', 'households.head_first_name', 'households.head_middle_name', 'households.head_last_name', 'households.head_suffix', 'zones.zone_number', 'households.contact_number')
            ->orderBy('zones.zone_number')
            ->orderBy('households.head_last_name')
            ->orderBy('households.head_first_name')
            ->limit($limit)
            ->get();

        if ($households->count() < $limit) {
            $firstChar = mb_substr($query, 0, 1);

            if ($firstChar !== '') {
                $candidates = DB::table('households')
                    ->join('zones', 'households.zone_id', '=', 'zones.id')
                    ->where('households.head_last_name', 'LIKE', "{$firstChar}%")
                    ->whereNotIn('households.id', $households->pluck('id'))
                    ->distinct()
                    ->select('households.id', 'households.head_first_name', 'households.head_middle_name', 'households.head_last_name', 'households.head_suffix', 'zones.zone_number', 'households.contact_number')
                    ->limit(300)
                    ->get();

                $fuzzy = app(FuzzySearchService::class)->rank(
                    $candidates,
                    $query,
                    fn ($h): string => fullName($h->head_last_name, $h->head_first_name, $h->head_middle_name, $h->head_suffix),
                    $limit - $households->count()
                );

                $households = $households->concat($fuzzy);
            }
        }

        $results = $households->take($limit)->map(function ($h) {
            $contact = $h->contact_number ? trim((string) $h->contact_number) : null;
            $headName = fullName($h->head_last_name, $h->head_first_name, $h->head_middle_name, $h->head_suffix);

            return [
                'id' => $h->id,
                'text' => $headName,
                'subtext' => 'Head: '.$headName.' | Zone '.$h->zone_number.' | Household #'.$h->id.($contact ? ' | '.$contact : ''),
            ];
        });

        return response()->json($results);
    }

    /**
     * Search for birth place locations (city/municipality + province)
     *
     * Matching city/municipality names rank ahead of province-only matches
     * (province capitals first), so a selected row always resolves to a
     * "City/Municipality, Province" string. The birth place field keeps a
     * free-text fallback for places this lookup does not cover.
     */
    public function locations(Request $request)
    {
        $query = $request->input('query');

        if (empty($query)) {
            return response()->json([]);
        }

        $like = '%'.$query.'%';
        $prefix = $query.'%';
        $limit = 15;

        $results = DB::table('municipalities')
            ->join('provinces', 'provinces.id', '=', 'municipalities.province_id')
            ->where(function ($qb) use ($like) {
                $qb->where('municipalities.name', 'LIKE', $like)
                    ->orWhere('provinces.name', 'LIKE', $like);
            })
            ->select('municipalities.id', 'municipalities.name', 'municipalities.is_capital', 'provinces.name as province_name')
            ->selectRaw('CASE WHEN municipalities.name LIKE ? THEN 0 WHEN municipalities.name LIKE ? THEN 1 ELSE 2 END as match_rank', [$prefix, $like])
            ->orderBy('match_rank')
            ->orderByDesc('is_capital')
            ->orderBy('municipalities.name')
            ->limit($limit)
            ->get()
            ->map(fn ($m) => [
                'id' => (int) $m->id,
                'text' => (string) $m->name,
                'subtext' => (string) $m->province_name,
            ]);

        return response()->json($results);
    }
}
