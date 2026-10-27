<?php

namespace Tests\Unit;

use App\Services\InstalledThemes;
use PHPUnit\Framework\TestCase;

class InstalledThemesTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/themes-' . uniqid();
        mkdir($this->dir . '/views/partials', 0777, true);
        touch($this->dir . '/views/atlas.blade.php');
        touch($this->dir . '/views/partials/header.blade.php');

        // Installed from the store
        mkdir($this->dir . '/installed/vega/views', 0777, true);
        touch($this->dir . '/installed/vega/views/theme.blade.php');
        file_put_contents($this->dir . '/installed/vega/manifest.json', '{"name":"Vega","version":"1.0.0"}');
        file_put_contents($this->dir . '/installed/vega/.installed.json', '{"signed":true}');
        // A folder without a view is not a usable theme.
        mkdir($this->dir . '/installed/broken', 0777, true);
        file_put_contents($this->dir . '/installed/broken/manifest.json', '{"name":"Broken"}');
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->dir));
    }

    private function themes(): InstalledThemes
    {
        return new InstalledThemes($this->dir . '/views', $this->dir . '/installed');
    }

    public function test_lists_built_in_and_installed_themes_with_atlas_first(): void
    {
        $themes = $this->themes();

        $this->assertSame(['atlas', 'vega'], array_column($themes->all(), 'id'));
        $this->assertSame('1.0.0', $themes->get('vega')['version']);
        $this->assertTrue($themes->isInstalledPackage('vega'));
        $this->assertFalse($themes->isInstalledPackage('atlas'));
    }

    public function test_resolves_views_and_falls_back_to_atlas(): void
    {
        $themes = $this->themes();

        $this->assertSame('frontend.theme.atlas', $themes->view('atlas'));
        $this->assertSame('themes::vega.views.theme', $themes->view('vega'));
        $this->assertSame('frontend.theme.atlas', $themes->view('broken'));
        $this->assertSame('frontend.theme.atlas', $themes->view('../secret'));
        $this->assertSame('frontend.theme.atlas', $themes->view(null));
    }
}
