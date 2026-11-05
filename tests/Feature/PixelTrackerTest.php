<?php

namespace Tests\Feature;

use App\Events\FrontendVisited;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PixelTrackerTest extends TestCase
{
    public function test_page_visit_dispatches_the_visit_event_and_returns_an_uncached_gif(): void
    {
        Event::fake([FrontendVisited::class]);

        $response = $this->get('/pixel-tracker?event=page_visit&tracking_id=abc123&t=1');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/gif');
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        Event::assertDispatched(FrontendVisited::class, fn ($e) => $e->data['tracking_id'] === 'abc123');
    }

    public function test_other_events_are_ignored(): void
    {
        Event::fake([FrontendVisited::class]);

        $this->get('/pixel-tracker?event=other')->assertOk();

        Event::assertNotDispatched(FrontendVisited::class);
    }

    public function test_tracking_snippet_does_not_depend_on_jquery(): void
    {
        $html = view('common.pixelTracking')->render();

        $this->assertStringNotContainsString('$(', $html);
        $this->assertStringContainsString('pixel-tracker', $html);
    }
}
