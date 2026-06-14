<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    public function testHealthEndpointReportsSafeApplicationMetadata(): void
    {
        $version = trim(file_get_contents(base_path('VERSION')));

        $response = $this->getJson('/health');

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
                'version' => $version,
                'release_tag' => 'v' . $version,
                'environment' => app()->environment(),
            ])
            ->assertJsonStructure([
                'status',
                'version',
                'release_tag',
                'environment',
                'timestamp',
            ])
            ->assertJsonMissingPath('app_key')
            ->assertJsonMissingPath('debug')
            ->assertJsonMissingPath('database')
            ->assertJsonMissingPath('secret');

        $this->assertNotFalse(strtotime($response->json('timestamp')));
    }

    public function testReadinessEndpointReportsReadyAfterDatabaseProbeSucceeds(): void
    {
        $version = trim(file_get_contents(base_path('VERSION')));

        DB::shouldReceive('select')
            ->once()
            ->with('select 1')
            ->andReturn([(object) ['ready' => 1]]);

        $response = $this->getJson('/ready');

        $response
            ->assertOk()
            ->assertJson([
                'status' => 'ready',
                'version' => $version,
                'release_tag' => 'v' . $version,
                'environment' => app()->environment(),
            ])
            ->assertJsonStructure([
                'status',
                'version',
                'release_tag',
                'environment',
                'timestamp',
            ])
            ->assertJsonMissingPath('database')
            ->assertJsonMissingPath('exception')
            ->assertJsonMissingPath('secret');

        $this->assertNotFalse(strtotime($response->json('timestamp')));
    }

    public function testReadinessEndpointReportsUnavailableWhenDatabaseProbeFails(): void
    {
        DB::shouldReceive('select')
            ->once()
            ->with('select 1')
            ->andThrow(new RuntimeException('database unavailable'));

        $response = $this->getJson('/ready');

        $response
            ->assertStatus(503)
            ->assertJson([
                'status' => 'unavailable',
            ])
            ->assertJsonMissingPath('database')
            ->assertJsonMissingPath('exception')
            ->assertJsonMissingPath('message')
            ->assertJsonMissingPath('secret');
    }
}
