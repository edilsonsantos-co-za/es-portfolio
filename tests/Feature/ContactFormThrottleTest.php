<?php

namespace Tests\Feature;

use Tests\TestCase;

class ContactFormThrottleTest extends TestCase
{
    public function testContactFormIsRateLimitedPerVisitor(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $status = $this->postJson('/api/v1/messages', [])->getStatusCode();
            $this->assertNotSame(429, $status);
        }

        $this->postJson('/api/v1/messages', [])->assertStatus(429);
    }

    public function testOtherVisitorsAreNotAffected(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->postJson('/api/v1/messages', []);
        }

        $status = $this->withServerVariables(['REMOTE_ADDR' => '10.9.9.9'])
            ->postJson('/api/v1/messages', [])->getStatusCode();
        $this->assertNotSame(429, $status);
    }
}
