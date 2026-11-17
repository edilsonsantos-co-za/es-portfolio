<?php

namespace Tests\Feature;

use App\Http\Controllers\Frontend\CvController;
use App\Models\LearningItem;
use App\Services\Contracts\FrontendInterface;
use CoreConstants;
use Tests\TestCase;

class GeneratedCvTest extends TestCase
{
    private function data(array $visibility = []): array
    {
        return [
            'portfolioConfig' => ['visibility' => array_replace(array_fill_keys(['cv', 'about', 'experiences', 'education', 'skills', 'projects'], true), $visibility)],
            'about' => (object) [
                'name' => 'Jane Doe', 'email' => 'jane@example.invalid', 'address' => 'Cape Town', 'phone' => '0000000000',
                'description' => '<p>Builds <strong>APIs</strong>.</p><script>alert(1)</script>',
                'taglines' => '["Full-Stack Engineer"]',
                'social_links' => '[{"title":"github","link":"https://github.com/jane/"},{"title":"bad","link":"javascript:alert(1)"}]',
            ],
            'experiences' => [
                (object) ['id' => 1, 'company' => 'Old Co', 'position' => 'Developer', 'period' => '2015 - 2020', 'details' => '<ul><li>Shipped</li></ul>'],
                (object) ['id' => 2, 'company' => 'New Co', 'position' => 'Engineer', 'period' => '2020 - Present', 'details' => null],
            ],
            'education' => [(object) ['institution' => 'Uni', 'degree' => 'BSc', 'period' => '2013-2015', 'department' => null, 'cgpa' => null]],
            'certificates' => [new LearningItem(['kind' => 'certificate', 'title' => 'AWS Course', 'issuer' => 'AWS', 'completed_on' => '2024-08-26'])],
            'plans' => [new LearningItem(['kind' => 'plan', 'title' => 'Future goal', 'issuer' => 'Someone'])],
            'skills' => [(object) ['name' => 'PHP', 'proficiency' => 90], (object) ['name' => 'SYMFONY', 'proficiency' => 75]],
            'projects' => [(object) ['title' => 'Site', 'details' => '<p>A site.</p>', 'link' => 'https://www.example.com/#']],
            'services' => [],
        ];
    }

    public function testTheCvIsBuiltFromPortfolioContentOnly(): void
    {
        $cv = CvController::content($this->data());

        $this->assertSame('Jane Doe', $cv['name']);
        $this->assertSame('Full-Stack Engineer', $cv['headline']);
        $this->assertSame(['New Co', 'Old Co'], array_column($cv['experiences'], 'company'), 'newest job first');
        $this->assertSame(['PHP', 'Symfony'], $cv['skills']);
        $this->assertSame([['title' => 'AWS Course', 'issuer' => 'AWS', 'date' => 'Aug 2024']], $cv['courses']);
        $this->assertSame('example.com', $cv['projects'][0]['link']);
        $this->assertSame([['title' => 'Github', 'url' => 'github.com/jane']], $cv['links'], 'only http(s) links');
        $this->assertStringNotContainsString('<script', $cv['summary']);
        $this->assertStringContainsString('<strong>APIs</strong>', $cv['summary']);
        $this->assertArrayNotHasKey('phone', $cv, 'the site never shows the phone number, so neither does the CV');
        $this->assertStringNotContainsString('Future goal', json_encode($cv), 'plans are goals, not qualifications');
    }

    public function testHiddenSectionsStayOutOfTheCv(): void
    {
        $cv = CvController::content($this->data(['experiences' => false, 'skills' => false]));

        $this->assertSame([], $cv['experiences']);
        $this->assertSame([], $cv['skills']);
    }

    public function testTheCvDownloadsAsAPdfAndRespectsItsSwitch(): void
    {
        $this->fake($this->data());
        $response = $this->get('/cv')->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());

        $this->fake($this->data(['cv' => false]));
        $this->get('/cv')->assertNotFound();
    }

    private function fake(array $payload): void
    {
        $this->app->instance(FrontendInterface::class, new class ($payload) implements FrontendInterface {
            public function __construct(private array $payload) {}
            public function getAllData() { return ['status' => CoreConstants::STATUS_CODE_SUCCESS, 'payload' => $this->payload]; }
            public function getAllProjects() { return ['status' => CoreConstants::STATUS_CODE_SUCCESS, 'payload' => $this->payload['projects']]; }
        });
    }
}
