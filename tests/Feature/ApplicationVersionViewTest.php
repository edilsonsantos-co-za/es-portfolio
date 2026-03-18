<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ApplicationVersionViewTest extends TestCase
{
    public function testApplicationVersionMetadataIsSharedWithViews(): void
    {
        $version = trim(file_get_contents(base_path('VERSION')));

        $this->assertSame($version, view()->shared('appVersion'));
        $this->assertSame('v' . $version, view()->shared('appReleaseTag'));
    }

    public function testBladeCanRenderTheSharedReleaseTag(): void
    {
        $version = trim(file_get_contents(base_path('VERSION')));

        $rendered = Blade::render('<span>{{ $appReleaseTag }}</span>');

        $this->assertStringContainsString('v' . $version, $rendered);
    }
}
