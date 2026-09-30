<?php

namespace Tests\Feature;

use Tests\TestCase;

class CorsPolicyTest extends TestCase
{
    public function testApiDoesNotAllowArbitraryOrigins(): void
    {
        config(['cors.allowed_origins' => ['https://edilsonsantos.co.za']]);

        $response = $this->getJson('/api/v1/status', ['Origin' => 'https://evil.example'])->assertOk();

        // A single allowed origin is always echoed as-is, so browsers reject others.
        $this->assertNotContains(
            $response->headers->get('Access-Control-Allow-Origin'),
            ['*', 'https://evil.example']
        );
    }

    public function testApiAllowsConfiguredOrigin(): void
    {
        config(['cors.allowed_origins' => ['https://edilsonsantos.co.za']]);

        $this->getJson('/api/v1/status', ['Origin' => 'https://edilsonsantos.co.za'])
            ->assertOk()
            ->assertHeader('Access-Control-Allow-Origin', 'https://edilsonsantos.co.za');
    }
}
