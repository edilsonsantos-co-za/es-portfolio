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
        mkdir($this->dir . '/partials', 0777, true);
        foreach (['vega', 'atlas', 'procyon'] as $id) {
            touch($this->dir . "/{$id}.blade.php");
        }
        touch($this->dir . '/partials/header.blade.php');
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->dir));
    }

    public function test_lists_installed_themes_with_atlas_first(): void
    {
        $themes = new InstalledThemes($this->dir);

        $this->assertSame(['atlas', 'procyon', 'vega'], array_column($themes->all(), 'id'));
    }

    public function test_falls_back_to_atlas_for_unknown_theme(): void
    {
        $themes = new InstalledThemes($this->dir);

        $this->assertSame('frontend.theme.vega', $themes->view('vega'));
        $this->assertSame('frontend.theme.atlas', $themes->view('../secret'));
        $this->assertSame('frontend.theme.atlas', $themes->view(null));
    }
}
