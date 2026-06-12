<?php

namespace Tests\Feature;

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
}
