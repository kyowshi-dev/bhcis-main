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
 * Sweeps every GET route with seeded data to catch render-time failures
 * (e.g. undefined route() names in Blade) that only surface with real rows.
 */
class RouteSweepTest extends TestCase
{
    use RefreshDatabase;

    /** Routes skipped because they need a real on-disk/temp file, not a database row. */
    private const SKIP_PREFIXES = [
        'livewire/preview-file/',
        'storage/',
    ];

    public function test_all_get_routes_render_without_server_errors(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@bhcis.com')->firstOrFail();
        $this->actingAs($admin);

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
        fwrite(STDERR, "\nRoute sweep (GET, admin): {$swept} routes - ".json_encode($statusCounts)."\n"
            .implode("\n", $nonOk)."\n");

        $this->assertGreaterThan(50, $swept, 'Route sweep did not cover the expected number of GET routes.');
        $this->assertSame([], $failures, "GET routes returned server errors:\n".implode("\n", $failures));
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
