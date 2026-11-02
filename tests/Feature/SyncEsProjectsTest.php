<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SyncEsProjectsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('database.connections.testing_projects', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        Config::set('database.default', 'testing_projects');
        DB::purge('testing_projects');
        Schema::create('projects', function ($table) {
            $table->id();
            $table->string('title');
            $table->text('categories');
            $table->text('thumbnail');
            $table->text('images');
            $table->text('details')->nullable();
            $table->text('link')->nullable();
            $table->timestamps();
        });
    }

    public function testSyncIsIdempotentPreservesExistingWorkAndExcludesMarketplace(): void
    {
        $portfolio = Project::create(['title' => 'Portfolio', 'categories' => '["Personal"]', 'thumbnail' => 'custom.png', 'images' => '["custom.png"]', 'details' => 'My custom portfolio description']);
        Project::create(['title' => 'Professional project', 'categories' => '["Professional"]', 'thumbnail' => 'client.png', 'images' => '["client.png"]']);
        foreach ([1, 2] as $run) {
            $this->artisan('es:projects:sync')->assertSuccessful();
        }
        $this->assertSame(6, Project::count());
        $this->assertSame('ES Portfolio', $portfolio->fresh()->title);
        $this->assertSame('custom.png', $portfolio->fresh()->thumbnail);
        $this->assertSame('My custom portfolio description', $portfolio->fresh()->details);
        $this->assertDatabaseHas('projects', ['title' => 'Professional project']);
        $this->assertDatabaseMissing('projects', ['title' => 'ES Marketplace']);
        $this->assertNull(Project::where('title', 'ES Labs')->value('link'));
        $this->assertSame(['Personal'], json_decode(Project::where('title', 'ES Blog')->value('categories'), true));
    }

    public function testDryRunDoesNotWriteAndUnsafeUrlsAreRejected(): void
    {
        $this->artisan('es:projects:sync', ['--dry-run' => true])->assertSuccessful();
        $this->assertSame(0, Project::count());
        $this->artisan('es:projects:sync', ['--developers-url' => 'javascript:alert(1)'])->assertFailed();
        $this->assertSame(0, Project::count());
    }

    public function testCommittedScreenshotReplacesOnlyThePlaceholder(): void
    {
        $dir = public_path('assets/projects');
        $made = !is_dir($dir) && mkdir($dir, 0777, true);
        $file = $dir . '/es-labs.jpg';
        $existed = is_file($file);
        $existed || touch($file);
        try {
            Project::create(['title' => 'ES Labs', 'categories' => '["Personal"]', 'thumbnail' => 'assets/brand/es-mark.svg', 'images' => json_encode(['assets/brand/es-mark.svg'])]);
            Project::create(['title' => 'ES Blog', 'categories' => '["Personal"]', 'thumbnail' => 'custom.png', 'images' => '["custom.png"]']);
            $this->artisan('es:projects:sync')->assertSuccessful();
            $this->assertSame('assets/projects/es-labs.jpg', Project::where('title', 'ES Labs')->value('thumbnail'));
            $this->assertSame(['assets/projects/es-labs.jpg'], json_decode(Project::where('title', 'ES Labs')->value('images'), true));
            $this->assertSame('custom.png', Project::where('title', 'ES Blog')->value('thumbnail'));
        } finally {
            $existed || unlink($file);
            $made && rmdir($dir);
        }
    }
}
