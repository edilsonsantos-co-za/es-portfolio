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

    public function testHomeIsTheHeroOnlyAndLinksToTheProjectsPage(): void
    {
        $this->fakeFrontend();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('href="' . route('frontend.projects') . '"', $html);
        $this->assertStringContainsString('id="clock"', $html);
        $this->assertStringNotContainsString('class="tile work"', $html);
        $this->assertStringNotContainsString('id="contact"', $html);
        $this->assertStringNotContainsString('id="services"', $html);
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
        $this->assertStringContainsString('href="' . route('frontend.contact') . '"', $html);
    }

    public function testProjectCardsLinkToTheirDetailPage(): void
    {
        $this->fakeFrontend();

        $html = $this->get('/projects')->assertOk()->getContent();

        $this->assertStringContainsString('href="' . route('frontend.project', 'project-3') . '"', $html);
        $this->assertSame(8, substr_count($html, 'View details'));
    }

    public function testProjectDetailPageShowsTheProject(): void
    {
        $this->fakeFrontend(['projects' => [2 => (object) [
            'id' => 3, 'title' => 'Project 3', 'categories' => json_encode(['Professional']), 'thumbnail' => null,
            'images' => json_encode(['projects/shot.png']), 'details' => "First line.\n\nSecond <b>para</b>.", 'link' => 'https://example.invalid/app',
        ]]]);

        $html = $this->get('/projects/project-3')->assertOk()->getContent();

        $this->assertStringContainsString('<title>Project 3 · Jane Doe</title>', $html);
        $this->assertStringContainsString('<h1>Project 3</h1>', $html);
        $this->assertStringContainsString('<p>First line.</p><p>Second para.</p>', $html);
        $this->assertStringContainsString('href="https://example.invalid/app"', $html);
        $this->assertStringContainsString('projects/shot.png', $html);
        $this->assertStringContainsString('href="' . route('frontend.projects') . '"', $html);
        $this->assertStringContainsString('rel="next"', $html);
        $this->assertStringNotContainsString('class="tile work"', $html);
    }

    public function testOldIdLinksRedirectToTheTitleUrlAndUnknownOnes404(): void
    {
        $this->fakeFrontend();

        $this->get('/projects/project-3')->assertOk();
        $this->get('/projects/3')->assertStatus(301)->assertRedirect(route('frontend.project', 'project-3'));
        $this->get('/projects/3-old-name')->assertStatus(301)->assertRedirect(route('frontend.project', 'project-3'));
        $this->get('/projects/99-nope')->assertNotFound();
        $this->get('/projects/no-such-project')->assertNotFound();
    }

    public function testProjectsPageRedirectsHomeWhenProjectsAreHidden(): void
    {
        $this->fakeFrontend(['portfolioConfig' => ['visibility' => ['projects' => false]]]);

        $this->get('/projects')->assertRedirect(url('/') . '#work');
    }

    public function testEveryNavItemIsItsOwnPage(): void
    {
        $this->fakeFrontend([
            'experiences' => [(object) ['id' => 1, 'company' => 'Acme', 'position' => 'Engineer', 'period' => '2024', 'details' => null]],
            'education' => [(object) ['id' => 1, 'institution' => 'UCT', 'degree' => 'BSc', 'department' => null, 'period' => '2010', 'cgpa' => null, 'thesis' => null]],
            'services' => [(object) ['title' => 'Web apps', 'details' => 'Built well']],
        ]);

        $home = $this->get('/')->assertOk()->getContent();
        $this->assertStringNotContainsString('href="#', $home);
        foreach (['experience', 'education', 'services', 'contact'] as $section) {
            $this->assertStringContainsString('href="' . route('frontend.' . $section) . '"', $home);
        }

        $html = $this->get('/experience')->assertOk()->getContent();
        $this->assertStringContainsString('<title>Experience · Jane Doe</title>', $html);
        $this->assertStringContainsString('<h1>Where I\'ve worked</h1>', $html);
        $this->assertStringContainsString('aria-current="page">Experience', $html);
        $this->assertStringNotContainsString('id="services"', $html);
        $this->assertStringNotContainsString('id="clock"', $html);

        $this->get('/services')->assertOk()->assertSee('How I can help');
        $this->get('/education')->assertOk()->assertSee('Learning, then building');
        $this->get('/contact')->assertOk()->assertSee('Got a project or a role?');
    }

    public function testHiddenSectionPageRedirectsToTheHomeAnchor(): void
    {
        $this->fakeFrontend(['portfolioConfig' => ['visibility' => ['services' => false]]]);

        $this->get('/services')->assertRedirect(url('/') . '#services');
        $this->get('/experience')->assertRedirect(url('/') . '#experience');
    }

    public function testEducationShowsInlineEvidenceAndSeparatesPlansWithoutAcademicRows(): void
    {
        $this->fakeFrontend();

        $this->get('/')->assertOk()->assertSee(route('frontend.education'));
        $response = $this->get('/education')->assertOk()
            ->assertSeeInOrder(['AWS Fargate - Overview', 'PHP Namespaces', 'AWS Skill Builder Learner Guide', 'AWS Cloud Practitioner Essentials', 'Getting Started with Amazon Simple Storage Service'])
            ->assertSee('not professional certification exams')
            ->assertSee('not qualifications already earned')
            ->assertSee('AWS Solutions Architect certification')
            ->assertSee('CompTIA DataSys+ certification')
            ->assertSee('Symfony certification')
            ->assertSee('University of the Witwatersrand')
            ->assertDontSee('Y Combinator');

        $html = $response->getContent();
        $this->assertSame(5, substr_count($html, '<details class="certificate-viewer">'));
        $this->assertSame(4, substr_count($html, '<span class="pill">Planned</span>'));
        $this->assertStringNotContainsString(' download', $html);
        $this->assertStringNotContainsString('.pdf', $html);
        $this->assertStringContainsString('https://symfonycasts.com/certificates/4F3A1984EC21', $html);
        foreach (config('education.certificates') as $certificate) {
            $path = public_path($certificate['image']);
            $this->assertFileExists($path);
            [$width, $height] = getimagesize($path);
            $this->assertSame([$certificate['width'], $certificate['height']], [$width, $height]);
        }
    }

    public function testEducationVisibilityAndEmptyContentStillControlThePage(): void
    {
        $this->fakeFrontend(['portfolioConfig' => ['visibility' => ['education' => false]]]);
        $this->get('/education')->assertRedirect(url('/') . '#education');
        $this->get('/')->assertDontSee('data-icon="education"', false);

        $this->fakeFrontend();
        config(['education.certificates' => [], 'education.plans' => []]);
        $this->get('/education')->assertRedirect(url('/') . '#education');
    }
}
