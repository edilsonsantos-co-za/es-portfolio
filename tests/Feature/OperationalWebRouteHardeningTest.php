<?php

namespace Tests\Feature;

use Tests\TestCase;

class OperationalWebRouteHardeningTest extends TestCase
{
    public function testDangerousOperationalRoutesAreUnavailableInProductionLikeConfig(): void
    {
        config([
            'app.env' => 'production',
            'app.operational_web_routes' => false,
        ]);

        $this->get('/command/cache:clear')->assertNotFound();
        $this->get('/admin/system-logs')->assertNotFound();
        $this->get('/optimize')->assertNotFound();
    }

    public function testHomeRouteStillWorksInProductionLikeConfig(): void
    {
        config([
            'app.env' => 'production',
            'app.operational_web_routes' => false,
        ]);

        $this->get('/')->assertOk();
    }
}
