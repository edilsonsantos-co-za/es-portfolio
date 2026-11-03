<?php

namespace Tests\Feature;

use App\Services\Contracts\FrontendInterface;
use CoreConstants;
use Tests\TestCase;

class AvatarRouteTest extends TestCase
{
    private function fakeAbout(?string $avatar): void
    {
        $about = (object) ['name' => 'Jane Doe', 'email' => 'jane@example.invalid', 'avatar' => $avatar];
        $this->app->instance(FrontendInterface::class, new class ($about) implements FrontendInterface {
            public function __construct(private object $about) {}
            public function getAllData()
            {
                return ['status' => CoreConstants::STATUS_CODE_SUCCESS, 'payload' => ['about' => $this->about]];
            }
            public function getAllProjects()
            {
                return ['status' => CoreConstants::STATUS_CODE_SUCCESS, 'payload' => []];
            }
        });
    }

    public function test_avatar_redirects_to_the_uploaded_portrait(): void
    {
        $this->fakeAbout('avatar/jane.jpg');

        $response = $this->get('/avatar');

        $response->assertRedirect();
        $this->assertStringContainsString('avatar/jane.jpg', (string) $response->headers->get('Location'));
    }

    public function test_avatar_falls_back_to_gravatar_without_an_upload(): void
    {
        $this->fakeAbout(null);

        $this->get('/avatar')->assertRedirect();
    }
}
