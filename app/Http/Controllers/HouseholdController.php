<?php

namespace App\Http\Controllers;

use App\Helpers\HouseholdHelper;
use App\Http\Requests\StoreHouseholdMemberRequest;
use App\Http\Requests\StoreHouseholdRequest;
use App\Http\Requests\UpdateHouseholdMemberRequest;
use App\Http\Requests\UpdateHouseholdRequest;
use App\Models\Household;
use App\Models\HouseholdMember;
use App\Services\HouseholdExportService;
use App\Services\HouseholdPdfService;
use App\Services\HouseholdQueryService;
use App\Services\HouseholdService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HouseholdController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizePermission('household');

        $user = auth()->user();

        $filters = [
            'search' => $request->input('search', ''),
            'zone_id' => $request->input('zone_id', ''),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
        ];

        $households = HouseholdQueryService::paginateIndex($filters, $user, pageSize(500));
        $allHouseholdsData = HouseholdQueryService::allFiltered($filters, $user);

        return view('households.index', [
            'households' => $households,
            'zones' => HouseholdQueryService::zones($user),
            'search' => $filters['search'],
            'zone_id' => $filters['zone_id'],
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
            'totalHouseholds' => $households->total(),
            'totalPopulation' => HouseholdHelper::getTotalPopulation($allHouseholdsData),
            'vulnerableGroups' => HouseholdHelper::getVulnerableGroupsCount($allHouseholdsData),
            'memberCounts' => HouseholdHelper::enrichHouseholdsWithMemberCounts($households),
        ]);
    }

    public function create(): View
    {
        $this->authorizePermission('household');

        return view('households.create', [
            'zones' => HouseholdQueryService::zones(auth()->user()),
        ]);
    }

    public function store(StoreHouseholdRequest $request): RedirectResponse
    {
        $this->authorizePermission('household');

        $data = $request->validated();

        $this->guardZoneAccess((int) $data['zone_id']);

        HouseholdService::create($data);

        return redirect()
            ->route('households.index')
            ->with('success', 'Household registered successfully.');
    }

    public function edit($id): View
    {
        $this->authorizePermission('household');

        $household = Household::findOrFail($id);

        if (! auth()->user()->canAccessHousehold((int) $household->id)) {
            abort(403, 'This household is outside your assigned zones.');
        }

        return view('households.edit', [
            'household' => $household,
            'zones' => HouseholdQueryService::zones(auth()->user()),
        ]);
    }

    public function update(UpdateHouseholdRequest $request, $id): RedirectResponse
    {
        $this->authorizePermission('household');

        $household = HouseholdService::findOrFail($id);

        if (! auth()->user()->canAccessHousehold((int) $household->id)) {
            abort(403, 'This household is outside your assigned zones.');
        }

        $data = $request->validated();

        $this->guardZoneAccess((int) $data['zone_id']);

        HouseholdService::update($id, $data);

        return redirect()
            ->route('households.index')
            ->with('success', 'Household updated successfully.');
    }

    /**
     * Export selected households to CSV
     */
    public function exportCSV(Request $request): StreamedResponse|RedirectResponse
    {
        $this->authorizePermission('household');

        $ids = $request->input('household_ids');
        if (! is_array($ids) || empty($ids)) {
            return redirect()
                ->route('households.index')
                ->with('error', 'Please select at least one household to export.');
        }

        // Sanitize IDs
        $ids = array_map('intval', $ids);

        $households = HouseholdQueryService::byIds($ids, false, auth()->user());
        $memberCounts = HouseholdHelper::enrichHouseholdsWithMemberCounts($households);

        $fileName = 'households_'.date('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        $callback = function () use ($households, $memberCounts) {
            $file = fopen('php://output', 'w');
            HouseholdExportService::writeCsvRows($file, $households, $memberCounts);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export selected households to PDF
     */
    public function exportPDF(Request $request): Response|RedirectResponse
    {
        $this->authorizePermission('household');

        $ids = $request->input('household_ids');
        if (! is_array($ids) || empty($ids)) {
            return redirect()
                ->route('households.index')
                ->with('error', 'Please select at least one household to export.');
        }

        // Sanitize IDs
        $ids = array_map('intval', $ids);

        $households = HouseholdQueryService::byIds($ids, true, auth()->user());
        $memberCounts = HouseholdHelper::enrichHouseholdsWithMemberCounts($households);
        $vulnerableGroups = HouseholdHelper::getVulnerableGroupsCount($households);
        $totalPopulation = HouseholdHelper::getTotalPopulation($households);

        return HouseholdPdfService::download(
            HouseholdPdfService::censusHtml($households, $memberCounts, $vulnerableGroups, $totalPopulation),
            'household_census_'.date('Y-m-d_His').'.pdf'
        );
    }

    /**
     * Zone scoping: zone-assigned workers may only write households in their
     * assigned zones.
     */
    private function guardZoneAccess(int $zoneId): void
    {
        $user = auth()->user();

        if ($user->isZoneScoped() && ! in_array($zoneId, $user->accessibleZoneIds(), true)) {
            abort(403, 'This zone is outside your assigned zones.');
        }
    }

    /**
     * Bulk update household zone
     */
    public function updateZone(Request $request): RedirectResponse
    {
        $this->authorizePermission('household');

        $data = $request->validate([
            'household_ids' => ['required', 'array', 'min:1'],
            'household_ids.*' => ['integer', 'exists:households,id'],
            'new_zone_id' => ['required', 'integer', 'exists:zones,id'],
        ]);

        $user = auth()->user();

        if ($user->isZoneScoped()) {
            $zoneIds = $user->accessibleZoneIds();
            $accessibleCount = Household::whereIn('id', $data['household_ids'])
                ->whereIn('zone_id', $zoneIds)
                ->count();

            if ($accessibleCount !== count($data['household_ids']) || ! in_array((int) $data['new_zone_id'], $zoneIds, true)) {
                abort(403, 'Households or target zone are outside your assigned zones.');
            }
        }

        HouseholdService::reassignZones($data['household_ids'], $data['new_zone_id']);

        return redirect()
            ->route('households.index')
            ->with('success', count($data['household_ids']).' household(s) reassigned to the new zone.');
    }

    public function show($id): View
    {
        $this->authorizePermission('household');

        $household = Household::findOrFail($id);

        if (! auth()->user()->canAccessHousehold((int) $household->id)) {
            abort(403, 'This household is outside your assigned zones.');
        }

        $members = $household->members()
            ->with('patient')
            ->whereNull('deleted_at')
            ->orderBy('relationship')
            ->orderBy('first_name')
            ->get();

        $patientCount = $household->patients()->whereNull('deleted_at')->count();

        return view('households.show', [
            'household' => $household,
            'members' => $members,
            'patientCount' => $patientCount,
        ]);
    }

    public function membersJson($id)
    {
        $household = Household::findOrFail($id);

        if (! auth()->user()->canAccessHousehold((int) $household->id)) {
            return response()->json(['error' => 'This household is outside your assigned zones.'], 403);
        }

        $members = HouseholdMember::query()
            ->where('household_id', $household->id)
            ->whereNull('deleted_at')
            ->with('patient:id,first_name,middle_name,last_name,suffix')
            ->orderBy('relationship')
            ->orderBy('first_name')
            ->get()
            ->map(function (HouseholdMember $member): array {
                $patientLinked = $member->patient !== null;

                return [
                    'id' => $member->id,
                    'full_name' => $member->full_name,
                    'first_name' => $member->first_name,
                    'middle_name' => $member->middle_name,
                    'last_name' => $member->last_name,
                    'suffix' => $member->suffix,
                    'sex' => $member->sex,
                    'age' => $member->age,
                    'date_of_birth' => $member->date_of_birth?->format('Y-m-d'),
                    'relationship' => $member->relationship,
                    'patient_linked' => $patientLinked,
                    'patient_name' => $member->patient ? fullName($member->patient->last_name, $member->patient->first_name, $member->patient->middle_name, $member->patient->suffix) : null,
                ];
            });

        return response()->json([
            'household' => [
                'id' => $household->id,
                'display_name' => $household->display_name,
                'head_first_name' => $household->head_first_name,
                'head_middle_name' => $household->head_middle_name,
                'head_last_name' => $household->head_last_name,
                'head_suffix' => $household->head_suffix,
                'zone_number' => $household->zone->zone_number ?? '',
                'contact_number' => $household->contact_number,
            ],
            'members' => $members,
        ]);
    }

    public function storeMember(StoreHouseholdMemberRequest $request, $id): RedirectResponse
    {
        $this->authorizePermission('household');

        $household = Household::findOrFail($id);

        if (! auth()->user()->canAccessHousehold((int) $household->id)) {
            abort(403, 'This household is outside your assigned zones.');
        }

        $this->guardZoneAccess((int) $household->zone_id);

        $data = $request->validated();
        $data['household_id'] = $household->id;

        HouseholdMember::create($data);

        return redirect()
            ->route('households.show', $household->id)
            ->with('success', 'Household member added successfully.');
    }

    public function updateMember(UpdateHouseholdMemberRequest $request, $id, $memberId): RedirectResponse
    {
        $this->authorizePermission('household');

        $household = Household::findOrFail($id);

        if (! auth()->user()->canAccessHousehold((int) $household->id)) {
            abort(403, 'This household is outside your assigned zones.');
        }

        $member = HouseholdMember::where('household_id', $household->id)
            ->where('id', $memberId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $this->guardZoneAccess((int) $household->zone_id);

        $data = $request->validated();

        $member->update($data);

        return redirect()
            ->route('households.show', $household->id)
            ->with('success', 'Household member updated successfully.');
    }

    public function destroyMember($id, $memberId): RedirectResponse
    {
        $this->authorizePermission('household');

        $household = Household::findOrFail($id);

        if (! auth()->user()->canAccessHousehold((int) $household->id)) {
            abort(403, 'This household is outside your assigned zones.');
        }

        $member = HouseholdMember::where('household_id', $household->id)
            ->where('id', $memberId)
            ->whereNull('deleted_at')
            ->firstOrFail();

        $this->guardZoneAccess((int) $household->zone_id);

        // Prevent deleting a member linked to a patient
        if ($member->patient_id !== null) {
            return redirect()
                ->route('households.show', $household->id)
                ->with('error', 'Cannot remove a member who is linked to a patient record.');
        }

        $member->delete();

        return redirect()
            ->route('households.show', $household->id)
            ->with('success', 'Household member removed successfully.');
    }
}
