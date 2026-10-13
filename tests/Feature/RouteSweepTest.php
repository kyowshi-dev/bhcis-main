<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Sweeps every GET route to catch render-time failures (e.g. undefined
 * route() names in Blade, null relation dereferences) that only surface
 * with real rows. Covers the seeded admin, each role, and an empty-data
 * edge case so zero-500 holds beyond the happy path.
 */
class RouteSweepTest extends TestCase
{
    use RefreshDatabase;

    /** Routes skipped because they need a real on-disk/temp file, not a database row. */
    private const SKIP_PREFIXES = [
        'livewire/preview-file/',
        'storage/',
    ];

    /** One seeded account per role, swept by the per-role regression. */
    private const ROLE_ACCOUNTS = [
        'Admin' => 'admin@bhcis.com',
        'Nurse' => 'nurse@bhcis.com',
        'Midwife' => 'midwife2@bhcis.com',
        'BHW' => 'bhw1@bhcis.com',
        'Doctor' => 'doctor@bhcis.com',
    ];

    /**
     * Clinical/transactional tables emptied for the zero-data sweep.
     * Lookups, zones, users, roles, and settings are intentionally kept.
     * Ordered children-first so restrict-on-delete FKs stay satisfied.
     */
    private const EMPTY_TABLES = [
        'immunization_status_events',
        'immunization_records',
        'vitals',
        'prescriptions',
        'diagnosis_records',
        'prenatal_visits',
        'postnatal_records',
        'family_planning_visits',
        'outward_referrals',
        'consultations',
        'pregnancies',
        'family_planning_clients',
        'maternal_profiles',
        'deaths',
        'household_members',
        'patients',
        'households',
        'notifications',
        'audit_logs',
    ];

    public function test_all_get_routes_render_without_server_errors(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@bhcis.com')->firstOrFail();
        $this->actingAs($admin);

        $result = $this->sweepGetRoutes();

        fwrite(STDERR, "\nRoute sweep (GET, admin): {$result['swept']} routes - ".json_encode($result['statusCounts'])."\n"
            .implode("\n", $result['nonOk'])."\n");

        $this->assertGreaterThan(50, $result['swept'], 'Route sweep did not cover the expected number of GET routes.');
        $this->assertSame([], $result['failures'], "GET routes returned server errors:\n".implode("\n", $result['failures']));
    }

    public function test_all_get_routes_render_without_server_errors_for_each_role(): void
    {
        $this->seed(DatabaseSeeder::class);

        $failures = [];
        $sweptTotals = [];

        foreach (self::ROLE_ACCOUNTS as $role => $email) {
            $user = User::where('email', $email)->firstOrFail();
            $this->actingAs($user);

            $result = $this->sweepGetRoutes();
            $sweptTotals[$role] = $result['swept'];

            fwrite(STDERR, "\nRoute sweep (GET, {$role}): {$result['swept']} routes - ".json_encode($result['statusCounts'])."\n");

            foreach ($result['failures'] as $failure) {
                $failures[] = "[{$role}] {$failure}";
            }
        }

        foreach ($sweptTotals as $role => $swept) {
            $this->assertGreaterThan(50, $swept, "Route sweep for role {$role} did not cover the expected number of GET routes.");
        }

        $this->assertSame([], $failures, "GET routes returned server errors:\n".implode("\n", $failures));
    }

    public function test_all_get_routes_render_without_server_errors_with_empty_clinical_data(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->emptyClinicalTables();

        $admin = User::where('email', 'admin@bhcis.com')->firstOrFail();
        $this->actingAs($admin);

        $result = $this->sweepGetRoutes();

        fwrite(STDERR, "\nRoute sweep (GET, admin, empty data): {$result['swept']} routes - ".json_encode($result['statusCounts'])."\n"
            .implode("\n", $result['nonOk'])."\n");

        $this->assertGreaterThan(50, $result['swept'], 'Empty-data route sweep did not cover the expected number of GET routes.');
        $this->assertSame([], $result['failures'], "GET routes returned server errors with empty clinical data:\n".implode("\n", $result['failures']));
    }

    /**
     * @return array{failures: array<int, string>, statusCounts: array<int, int>, nonOk: array<int, string>, swept: int}
     */
    private function sweepGetRoutes(): array
    {
        $failures = [];
        $statusCounts = [];
        $nonOk = [];
        $swept = 0;

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = $route->uri();

            if (str_starts_with($uri, '_boost/') || $uri === '_boost') {
                continue;
            }

            foreach (self::SKIP_PREFIXES as $prefix) {
                if (str_starts_with($uri, $prefix)) {
                    continue 2;
                }
            }

            $url = $this->fillPlaceholders($uri);
            $status = $this->get($url)->getStatusCode();
            $swept++;
            $statusCounts[$status] = ($statusCounts[$status] ?? 0) + 1;

            if ($status !== 200) {
                $nonOk[] = $status.' '.$uri;
            }

            if ($status >= 500) {
                $failures[] = sprintf('%s %s -> %d', implode('|', $route->methods()), $uri, $status);
            }
        }

        ksort($statusCounts);

        return compact('failures', 'statusCounts', 'nonOk', 'swept');
    }

    private function emptyClinicalTables(): void
    {
        foreach (self::EMPTY_TABLES as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->delete();
            }
        }
    }

    private function fillPlaceholders(string $uri): string
    {
        return (string) preg_replace_callback('#\{(\w+)\}#', function (array $match) use ($uri) {
            $table = $this->tableFor($match[1], $uri);

            if ($table === null) {
                return '1';
            }

            $id = $this->firstId($table);

            return $id !== null ? (string) $id : '1';
        }, $uri);
    }

    private function tableFor(string $param, string $uri): ?string
    {
        $firstSegment = explode('/', $uri)[0];

        return match ($param) {
            'auditLog' => 'audit_logs',
            'patient' => 'patients',
            'consultation' => 'consultations',
            'client' => 'family_planning_clients',
            'postnatal' => 'postnatal_records',
            'pregnancy' => 'pregnancies',
            'role' => 'user_roles',
            'user' => 'users',
            'diagnosisId' => 'diagnosis_records',
            'prescriptionId' => 'prescriptions',
            'vitalId' => 'vitals',
            'visit' => 'prenatal_visits',
            'vaccine' => 'vaccines_lookup',
            'notification' => 'notifications',
            'id' => match ($firstSegment) {
                'medicines' => 'medicines_lookup',
                'referrals' => 'outward_referrals',
                'households', 'patients', 'users' => $firstSegment,
                default => null,
            },
            default => null,
        };
    }

    private function firstId(string $table): int|string|null
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        return DB::table($table)->orderBy('id')->value('id');
    }
}
