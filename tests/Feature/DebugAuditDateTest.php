<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DebugAuditDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_date_filters(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('email', 'admin@bhcis.com')->firstOrFail());

        $ok = $this->get('/activity-logs?date_from=01/01/2026&date_to=31/12/2026');
        fwrite(STDERR, 'valid d/m/Y -> '.$ok->getStatusCode()."\n");

        $bad = $this->get('/activity-logs?date_from=garbage');
        fwrite(STDERR, 'invalid date -> '.$bad->getStatusCode()."\n");

        $follow = $this->followingRedirects()->get('/activity-logs?date_from=garbage');
        $html = $follow->getContent();
        fwrite(STDERR, 'followed -> '.$follow->getStatusCode()
            .' shows_error='.(str_contains($html, 'date_from') && (str_contains($html, 'invalid') || str_contains($html, 'errors') || str_contains($html, 'The date')) ? 'yes' : 'no')."\n");

        // does filter actually narrow results?
        $all = $this->get('/activity-logs');
        $filtered = $this->get('/activity-logs?date_from=01/01/2099');
        fwrite(STDERR, 'all length='.strlen($all->getContent()).' future-date-filter length='.strlen($filtered->getContent())."\n");

        $this->assertTrue(true);
    }
}
