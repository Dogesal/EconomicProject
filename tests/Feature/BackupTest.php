<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_returns_404_when_using_in_memory_database(): void
    {
        // The test suite runs on an in-memory SQLite database, so there is no
        // file to stream — the endpoint should respond gracefully.
        $response = $this->get(route('settings.backup'));

        $response->assertNotFound();
    }
}
