<?php

namespace Tests\Unit;

use App\Services\ApplicationVersion;
use RuntimeException;
use Tests\TestCase;

class ApplicationVersionTest extends TestCase
{
    public function testReadsVersionAndReleaseTagFromVersionFile(): void
    {
        $service = new ApplicationVersion(base_path('VERSION'));

        $this->assertSame(trim(file_get_contents(base_path('VERSION'))), $service->version());
        $this->assertSame('v' . $service->version(), $service->releaseTag());
    }

    public function testThrowsWhenVersionFileIsMissing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Version file not found');

        new ApplicationVersion(base_path('VERSION.missing'));
    }

    public function testThrowsWhenVersionFileContainsInvalidSemanticVersion(): void
    {
        $tempPath = tempnam(sys_get_temp_dir(), 'version-test-');
        file_put_contents($tempPath, "invalid-version\n");

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('must use semantic version format X.Y.Z');

            new ApplicationVersion($tempPath);
        } finally {
            @unlink($tempPath);
        }
    }
}
