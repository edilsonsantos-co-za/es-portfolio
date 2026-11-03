<?php

namespace Tests\Feature;

use Tests\TestCase;

class AvatarRouteTest extends TestCase
{
    public function test_avatar_redirects_to_the_current_portrait(): void
    {
        $response = $this->get('/avatar');

        $response->assertRedirect();
        $this->assertNotSame('', (string) $response->headers->get('Location'));
    }
}
