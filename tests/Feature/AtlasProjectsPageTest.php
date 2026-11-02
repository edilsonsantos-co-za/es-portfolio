<?php

namespace Tests\Feature;

use App\Services\Contracts\FrontendInterface;
use CoreConstants;
use Tests\TestCase;

class AtlasProjectsPageTest extends TestCase
{
    private function fakeFrontend(array $overrides = [], int $projectCount = 8): void
    {
        $projects = [];
        for ($i = 1; $i <= $projectCount; $i++) {
            $projects[] = (object) [
                'id' => $i,
                'title' => 'Project ' . $i,
                'categories' => json_encode([$i % 2 ? 'Professional' : 'Personal']),
                'thumbnail' => null,
                'details' => 'Details ' . $i,
                'link' => null,
            ];
        }

        $visibility = array_fill_keys(['about', 'skills', 'skillProficiency', 'education', 'experiences', 'projects', 'services', 'contact', 'cv', 'footer'], true);
        $payload = array_replace_recursive([
            'portfolioConfig' => [
                'template' => 'atlas',
                'maintenanceMode' => CoreConstants::FALSE,
                'visibility' => $visibility,
                'seo' => ['title' => 'Jane Doe', 'description' => '', 'author' => '', 'image' => null],
                'script' => ['header' => null, 'footer' => null],
                'googleAnalyticsId' => null,
                'accentColor' => null,
            ],
            'about' => (object) ['name' => 'Jane Doe', 'email' => 'jane@example.invalid', 'address' => 'Cape Town', 'description' => 'Hello', 'taglines' => null, 'social_links' => null, 'avatar' => null, 'cv' => null],
            'skills' => [],
            'education' => [],
            'experiences' => [],
            'projects' => $projects,
            'services' => [],
        ], $overrides);

        $this->app->instance(FrontendInterface::class, new class ($payload) implements FrontendInterface {
            public function __construct(private array $payload) {}
            public function getAllData()
            {
                return ['status' => CoreConstants::STATUS_CODE_SUCCESS, 'payload' => $this->payload];
            }
            public function getAllProjects()
            {
                return ['status' => CoreConstants::STATUS_CODE_SUCCESS, 'payload' => $this->payload['projects']];
            }
        });
    }

    public function testHomeLimitsProjectsAndLinksToTheProjectsPage(): void
    {
        $this->fakeFrontend();

        $response = $this->get('/')->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('href="' . route('frontend.projects') . '"', $html);
        $this->assertStringContainsString('View all 8', $html);
        $this->assertStringContainsString('data-filter="Professional"', $html);
        $this->assertStringContainsString('data-filter="Personal"', $html);
        $this->assertSame(8, preg_match_all('/class="tile work"/', $html));
        $this->assertSame(2, preg_match_all('/class="tile work"[^>]*\shidden\b/', $html));
    }

    public function testProjectsPageListsEveryProjectWithFilters(): void
    {
        $this->fakeFrontend();

        $html = $this->get('/projects')->assertOk()->getContent();

        $this->assertStringContainsString('<h1>All projects</h1>', $html);
        $this->assertStringContainsString('<title>Projects · Jane Doe</title>', $html);
        $this->assertSame(8, substr_count($html, 'class="tile work"'));
        $this->assertSame(0, preg_match_all('/class="tile work"[^>]*\shidden\b/', $html));
        $this->assertStringNotContainsString('id="clock"', $html);
        $this->assertStringContainsString('href="' . url('/') . '#contact"', $html);
    }

    public function testProjectsPageRedirectsHomeWhenProjectsAreHidden(): void
    {
        $this->fakeFrontend(['portfolioConfig' => ['visibility' => ['projects' => false]]]);

        $this->get('/projects')->assertRedirect(url('/') . '#work');
    }
}
