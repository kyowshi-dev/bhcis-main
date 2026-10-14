<?php

namespace Tests\Feature;

use Tests\TestCase;

class DatabaseTimezoneConfigTest extends TestCase
{
    /**
     * The MySQL session must be pinned to UTC to match config/app.php's timezone.
     * Without this, TIMESTAMP columns return the server's SYSTEM zone (+8 here)
     * wall-clock values, which Carbon parses as UTC - showing future times.
     */
    public function test_mysql_connections_pin_session_timezone_to_utc(): void
    {
        $this->assertSame('+00:00', config('database.connections.mysql.timezone'));
        $this->assertSame('+00:00', config('database.connections.mariadb.timezone'));
    }
}
