<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkerId;
use App\Http\Requests\AdministerBatchVaccineRequest;
use App\Http\Requests\AdministerVaccineRequest;
use App\Http\Requests\MarkNoShowRequest;
use App\Http\Requests\StoreInfantWithHouseholdRequest;
use App\Http\Requests\UpdateImmunizationRequest;
use App\Models\Household;
use App\Models\Immunization;
use App\Models\Patient;
use App\Models\Vaccine;
use App\Models\Zone;
use App\Services\ChildImmunizationService;
use App\Services\ImmunizationQueryService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ImmunizationController extends Controller
{
    use ResolvesWorkerId;

    public function __construct(
        private readonly ChildImmunizationService $service,
    ) {}

    public function index(Request $request): View
    {
        $this->authorizeImmunizations();

        $zoneId = $request->filled('zone_id') ? (int) $request->input('zone_id') : null;

        [$from, $to, $month, $dateFrom, $dateTo, $monthOptions] = $this->resolveDateWindow($request);

        $categories = ['Child', 'Adult', 'Both'];

        $queues = [];
        foreach (['due', 'overdue', 'no_show'] as $key) {
            $queues[$key] = $this->service->queue(
                $key,
                $zoneId,
                $key === 'due' ? $from : ($key === 'overdue' ? $from : null),
                $key === 'due' ? $to : null,
                $categories
            );
        }

        $dueTodayCount = $queues['due']->map(fn (array $entry) => $entry['patient']->id)->unique()->count();
        $overdueCount = $queues['overdue']->map(fn (array $entry) => $entry['patient']->id)->unique()->count();
        $noShowCount = $queues['no_show']->count();

        $dueTodayPatients = $queues['due']
            ->map(fn (array $entry) => (object) [
                'patient_id' => $entry['patient']->id,
                'first_name' => $entry['patient']->first_name,
                'last_name' => $entry['patient']->last_name,
                'due_date' => $entry['due_date']->toDateString(),
                'dose_number' => $entry['dose_number'],
                'vaccine_name' => $entry['vaccine']->vaccine_name,
            ])
            ->values();

        $recentRecords = $this->service->withNextDue(ImmunizationQueryService::recentRecords());

        $infantStats = ImmunizationQueryService::infantStats($zoneId);
        $adultEnrolled = ImmunizationQueryService::adultEnrolled($zoneId);

        $zones = Zone::orderBy('zone_number')->get();

        return view('immunizations.index', [
            'zones' => $zones,
            'zoneId' => $zoneId,
            'month' => $month,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'monthOptions' => $monthOptions,
            'dueWindowStart' => $from,
            'dueWindowEnd' => $to,
            'queues' => $queues,
            'recentRecords' => $recentRecords,
            'dueTodayPatients' => $dueTodayPatients,
            'dueTodayCount' => $dueTodayCount,
            'overdueCount' => $overdueCount,
            'noShowCount' => $noShowCount,
            'infantCoveragePercent' => $infantStats['infantCoveragePercent'],
            'infantTotal' => $infantStats['infantTotal'],
            'adultEnrolled' => $adultEnrolled,
        ]);
    }

    /**
     * Resolve the due-date window from the month filter or the optional
     * date range. Defaults to the current month when nothing is selected.
     *
     * @return array{0: Carbon, 1: Carbon, 2: string, 3: ?string, 4: ?string, 5: array<string, string>}
     */
    private function resolveDateWindow(Request $request): array
    {
        $today = Carbon::today();
        $monthOptions = [];
        for ($i = -11; $i <= 12; $i++) {
            $cursor = $today->copy()->addMonthsNoOverflow($i)->startOfMonth();
            $monthOptions[$cursor->format('Y-m')] = $cursor->format('F Y');
        }

        $currentMonth = $today->format('Y-m');
        $month = $request->filled('month')
            ? $request->input('month')
            : $currentMonth;

        if (! is_string($month) || ! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $month = $currentMonth;
        }

        $dateFrom = $request->filled('date_from') ? $request->input('date_from') : null;
        $dateTo = $request->filled('date_to') ? $request->input('date_to') : null;

        $from = null;
        $to = null;
        try {
            $from = $dateFrom !== null ? Carbon::parse($dateFrom)->startOfDay() : null;
            $to = $dateTo !== null ? Carbon::parse($dateTo)->endOfDay() : null;
        } catch (\Exception) {
            $from = null;
            $to = null;
        }

        if ($from === null && $to === null) {
            $from = Carbon::createFromFormat('Y-m-d', $month.'-01')->startOfDay();
            $to = $from->copy()->endOfMonth();
        } elseif ($from === null) {
            $from = $to->copy()->startOfDay();
        } elseif ($to === null) {
            $to = $from->copy()->endOfDay();
        }

        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to, $month, $dateFrom, $dateTo, $monthOptions];
    }

    public function forPatient($id): View
    {
        $this->authorizeImmunizations();

        $patient = Patient::with(['household', 'immunizationRecords'])->findOrFail($id);

        return view('immunizations.patient', $this->schedulePayload($patient));
    }

    /**
     * DOH-style printable child immunization record card.
     */
    public function printRecord($id): View
    {
        $this->authorizeImmunizations();

        $patient = Patient::with(['household.zone'])->findOrFail($id);

        return view('immunizations.print-card', $this->printPayload($patient));
    }

    /**
     * Card payload: patient, per-vaccine dose records, and the age-appropriate vaccine list.
     *
     * @return array<string, mixed>
     */
    private function printPayload(Patient $patient): array
    {
        $isChild = $patient->age < 18;
        $allowedCategories = $isChild ? ['Child', 'Both'] : ['Adult', 'Both'];

        return [
            'patient' => $patient,
            'records' => ImmunizationQueryService::recordsForPatient($patient->id),
            'vaccines' => Vaccine::whereIn('category', $allowedCategories)
                ->with('schedules')
                ->orderBy('sort_order')
                ->get(),
        ];
    }

    /**
     * Renders the inline check-in fragment used by the queue's expanded rows.
     */
    public function checkin(Patient $patient): View
    {
        $this->authorizeImmunizations();

        $patient->load(['household', 'immunizationRecords']);

        return view('immunizations.partials._checkin', $this->schedulePayload($patient));
    }

    /**
     * Shared schedule data for the patient page and the check-in fragment.
     *
     * @return array<string, mixed>
     */
    private function schedulePayload(Patient $patient): array
    {
        $isChild = $patient->age < 18;
        $allowedCategories = $isChild ? ['Child', 'Both'] : ['Adult', 'Both'];

        $records = ImmunizationQueryService::recordsForPatient($patient->id);
        $vaccines = ImmunizationQueryService::vaccinesFor($allowedCategories);
        $healthWorkers = ImmunizationQueryService::healthWorkers();

        $recordsByVaccine = $records->groupBy('vaccine_id');

        $latestRecordWith = fn (string $column) => $records
            ->first(fn ($record) => $record->{$column} !== null)
            ?->{$column};

        $previousMeasurements = (object) [
            'temp_recorded' => $latestRecordWith('temp_recorded'),
            'child_weight_kg' => $latestRecordWith('child_weight_kg'),
            'child_height_cm' => $latestRecordWith('child_height_cm'),
        ];

        $schedule = $vaccines->map(function ($vaccine) use ($recordsByVaccine) {
            $doses = $recordsByVaccine->get($vaccine->id, collect());
            $latestDose = $doses->sortByDesc('date_given')->first();

            return (object) [
                'vaccine' => $vaccine,
                'doses_given' => $doses->count(),
                'latest_date' => $latestDose?->date_given,
                'latest_dose_number' => $latestDose?->dose_number,
            ];
        });

        $vaccineModels = Vaccine::whereIn('category', $allowedCategories)->with('schedules')->get();
        $statuses = $vaccineModels->mapWithKeys(fn (Vaccine $v) => [$v->id => $this->service->statusFor($patient, $v)]);
        $eligibility = $vaccineModels->mapWithKeys(fn (Vaccine $v) => [$v->id => $this->service->eligibility($patient, $v)]);
        $schedulesByVaccine = $vaccineModels->mapWithKeys(fn (Vaccine $v) => [$v->id => $v->schedules]);

        $currentWorkerId = (int) $this->currentWorkerId();

        $noShowEvents = $vaccineModels->mapWithKeys(
            fn (Vaccine $v) => [$v->id => $this->service->unresolvedMissed($patient, $v)]
        );

        return [
            'patient' => $patient,
            'records' => $records,
            'vaccines' => $vaccines,
            'schedule' => $schedule,
            'healthWorkers' => $healthWorkers,
            'currentWorkerId' => $currentWorkerId,
            'statuses' => $statuses,
            'eligibility' => $eligibility,
            'schedulesByVaccine' => $schedulesByVaccine,
            'noShowEvents' => $noShowEvents,
            'previousMeasurements' => $previousMeasurements,
            'isImmunizationEnrolled' => $patient->is_immunization_enrolled,
        ];
    }

    public function createInfant(): View
    {
        $this->authorizeImmunizations();

        $zones = Zone::orderBy('zone_number')->get();

        return view('immunizations.enroll-infant', ['zones' => $zones]);
    }

    public function administer(AdministerVaccineRequest $request, $id): RedirectResponse
    {
        $patient = Patient::findOrFail($id);

        $vaccine = Vaccine::findOrFail($request->input('vaccine_id'));

        if (! $this->service->vaccineMatchesAge($patient, $vaccine)) {
            return back()->withErrors(['vaccine_id' => 'This vaccine is not appropriate for this patient.'])->withInput();
        }

        try {
            $record = $this->service->administer($patient, $vaccine, [
                'date_given' => $request->input('date_given'),
                'temp_recorded' => $request->input('temp_recorded'),
                'child_weight_kg' => $request->input('child_weight_kg'),
                'child_height_cm' => $request->input('child_height_cm'),
                'notes' => $request->input('notes'),
                'override_reason' => $request->input('override_reason'),
                'administered_by' => $this->currentWorkerId(),
            ]);
        } catch (ValidationException $e) {
            return redirect()
                ->route('immunizations.patient', $patient->id)
                ->withErrors($e->errors())
                ->withInput();
        }

        $next = '';
        $nextDue = $this->service->nextDoseDate($patient, $vaccine);

        if ($nextDue !== null && $nextDue->gt($record->date_given)) {
            $next = ' Next dose due '.$nextDue->format('M j, Y').'.';
        }

        return back()->with('success', $vaccine->vaccine_name.' recorded (dose '.$record->dose_number.').'.$next);
    }

    /**
     * Record several due doses for one patient in a single submission.
     *
     * All doses share one date, temperature, weight/height and notes. The
     * whole batch is atomic: any per-dose validation failure rolls back
     * every record written in the loop.
     */
    public function administerBatch(AdministerBatchVaccineRequest $request, $id): RedirectResponse
    {
        $patient = Patient::findOrFail($id);

        $vaccineIds = array_map('intval', $request->input('doses', []));
        $vaccines = Vaccine::whereIn('id', $vaccineIds)->get()->keyBy('id');

        foreach ($vaccineIds as $vaccineId) {
            $vaccine = $vaccines->get($vaccineId);
            if ($vaccine === null || ! $this->service->vaccineMatchesAge($patient, $vaccine)) {
                return back()->withErrors(['doses' => 'One or more selected vaccines are not appropriate for this patient.'])->withInput();
            }
        }

        $groupCounts = $vaccines->pluck('group_key')->filter()->countBy();
        if ($groupCounts->contains(fn (int $count) => $count > 1)) {
            return back()->withErrors(['doses' => 'Select only one option per vaccine group (e.g. Hepa B birth dose variants).'])->withInput();
        }

        $shared = [
            'date_given' => $request->input('date_given'),
            'temp_recorded' => $request->input('temp_recorded'),
            'child_weight_kg' => $request->input('child_weight_kg'),
            'child_height_cm' => $request->input('child_height_cm'),
            'notes' => $request->input('notes'),
            'override_reason' => $request->input('override_reason'),
            'administered_by' => $this->currentWorkerId(),
        ];

        $recorded = [];

        try {
            DB::transaction(function () use ($patient, $vaccineIds, $shared, &$recorded): void {
                foreach ($vaccineIds as $vaccineId) {
                    $vaccine = Vaccine::findOrFail($vaccineId);
                    $record = $this->service->administer($patient, $vaccine, $shared);
                    $recorded[] = [$vaccine, $record];
                }
            });
        } catch (ValidationException $e) {
            return redirect()
                ->route('immunizations.patient', $patient->id)
                ->withErrors($e->errors())
                ->withInput();
        }

        $names = collect($recorded)
            ->map(fn (array $pair) => $pair[0]->vaccine_name.' (dose '.$pair[1]->dose_number.')')
            ->implode(', ');

        return back()->with('success', count($recorded).' dose(s) recorded: '.$names.'.');
    }

    /**
     * Quick "Mark as done" for patients who received the dose elsewhere
     * (hospital, another facility). Records with today's date when the
     * actual date is unknown.
     */
    public function markGiven($id, Vaccine $vaccine): RedirectResponse
    {
        $patient = Patient::findOrFail($id);

        if (! $this->service->vaccineMatchesAge($patient, $vaccine)) {
            return back()->withErrors(['vaccine_id' => 'This vaccine is not appropriate for this patient.']);
        }

        try {
            $record = $this->service->administer($patient, $vaccine, [
                'date_given' => null,
                'temp_recorded' => null,
                'notes' => 'Marked as done - administered elsewhere',
                'override_reason' => null,
                'administered_elsewhere' => true,
                'administered_by' => $this->currentWorkerId(),
            ]);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()
            ->route('immunizations.index')
            ->with('success', $vaccine->vaccine_name.' (dose '.$record->dose_number.') marked as done - recorded as '.$record->date_given->format('M j, Y').'.');
    }

    /**
     * Update an existing immunization dose record.
     */
    public function update(UpdateImmunizationRequest $request, $id, Immunization $immunization): RedirectResponse
    {
        $this->authorizeImmunizations();

        $patient = $immunization->patient;

        if (auth()->user()->isZoneScoped() && ! auth()->user()->canAccessPatient($patient)) {
            abort(403);
        }

        $immunization->update($request->validated());

        return redirect()
            ->route('immunizations.patient', $patient->id)
            ->with('success', 'Immunization record updated successfully.');
    }

    public function enrollInfant(StoreInfantWithHouseholdRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Enrollment may target any household/zone; the enrolling worker is
        // recorded via patients.enrolled_by so they keep access to the infant.
        try {
            $patient = $this->service->enrollInfant($validated);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()
            ->route('immunizations.patient', $patient->id)
            ->with('success', 'Infant enrolled ('.$patient->first_name.' '.$patient->last_name.').');
    }

    public function toggleNoShow(MarkNoShowRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $patient = Patient::findOrFail($validated['patient_id']);

        $vaccine = Vaccine::findOrFail($validated['vaccine_id']);

        if ($validated['no_show']) {
            $this->service->markNoShow($patient, $vaccine, $validated['dose_number'] ?? null);
            $message = $patient->first_name.' '.$patient->last_name.' marked as no-show.';
        } else {
            $cleared = $this->service->clearNoShow($patient, $vaccine);
            $message = $cleared !== null
                ? 'No-show cleared; it stays in the patient history. You can now administer the dose.'
                : 'No unresolved no-show to clear.';
        }

        return back()->with('success', $message);
    }

    public function enroll(Patient $patient): RedirectResponse
    {
        $this->authorizeImmunizations();

        $this->service->enrollPatient($patient);

        return back()->with('success', $patient->first_name.' '.$patient->last_name.' enrolled in immunization program.');
    }

    public function householdMatch(Request $request): JsonResponse
    {
        $this->authorizeImmunizations();

        $validated = $request->validate([
            'surname' => ['required', 'string', 'max:255'],
            'zone_id' => ['nullable', 'integer', 'exists:zones,id'],
        ]);

        $query = DB::table('households')
            ->join('zones', 'households.zone_id', '=', 'zones.id')
            ->leftJoin('patients', 'patients.household_id', '=', 'households.id')
            ->where(function ($qb) use ($validated) {
                $qb->where('households.head_last_name', 'like', '%'.addcslashes($validated['surname'], '%_').'%')
                    ->orWhere('households.head_first_name', 'like', '%'.addcslashes($validated['surname'], '%_').'%');
            });

        if (! empty($validated['zone_id'])) {
            $query->where('households.zone_id', $validated['zone_id']);
        }

        $results = $query->distinct()
            ->select('households.id', 'households.head_first_name', 'households.head_middle_name', 'households.head_last_name', 'households.head_suffix', 'households.zone_id', 'zones.zone_number')
            ->selectRaw('COUNT(DISTINCT patients.id) as patients_count')
            ->groupBy('households.id', 'households.head_first_name', 'households.head_middle_name', 'households.head_last_name', 'households.head_suffix', 'households.zone_id', 'zones.zone_number')
            ->orderBy('households.head_last_name')
            ->orderBy('households.head_first_name')
            ->limit(10)
            ->get();

        return response()->json(
            $results->map(function ($h) {
                return [
                    'id' => $h->id,
                    'display_name' => fullName($h->head_last_name, $h->head_first_name, $h->head_middle_name, $h->head_suffix),
                    'zone_id' => $h->zone_id,
                    'zone' => ['zone_number' => $h->zone_number],
                    'patients_count' => $h->patients_count,
                ];
            })->values()
        );
    }

    public function motherMatch(Request $request): JsonResponse
    {
        $this->authorizeImmunizations();

        $validated = $request->validate([
            'query' => ['required', 'string', 'max:255'],
        ]);

        $adultCutoff = Carbon::today()->subYears(18)->toDateString();

        $results = Patient::query()
            ->with('household.zone')
            ->where('sex', 'Female')
            ->whereDate('date_of_birth', '<=', $adultCutoff)
            ->where(function ($query) use ($validated) {
                $needle = addcslashes($validated['query'], '%_');
                $query->where('last_name', 'like', "%{$needle}%")
                    ->orWhere('first_name', 'like', "%{$needle}%");
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->limit(10)
            ->get();

        return response()->json(
            $results->map(fn (Patient $mother) => [
                'id' => $mother->id,
                'text' => fullName($mother->last_name, $mother->first_name, $mother->middle_name, $mother->suffix),
                'subtext' => $mother->patient_code.' | '.$mother->age.' y/o'.' | Purok '.$mother->household->zone->zone_number,
            ])->values()
        );
    }

    private function authorizeImmunizations(): void
    {
        if (! auth()->check() || ! auth()->user()->hasPermission('immunizations')) {
            abort(403, 'Unauthorized');
        }
    }
}
