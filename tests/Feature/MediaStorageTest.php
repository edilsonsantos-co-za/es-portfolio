<?php

namespace Tests\Feature;

use App\Helpers\MediaStorage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaStorageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $publicRoot = storage_path('framework/testing/disks/public-media');
        $s3Root = storage_path('framework/testing/disks/s3-media');

        File::deleteDirectory($publicRoot);
        File::deleteDirectory($s3Root);
        File::ensureDirectoryExists($publicRoot);
        File::ensureDirectoryExists($s3Root);

        config([
            'filesystems.media_disk' => 's3',
            'filesystems.disks.public' => [
                'driver' => 'local',
                'root' => $publicRoot,
                'url' => env('APP_URL').'/storage',
                'visibility' => 'public',
            ],
            'filesystems.disks.s3' => [
                'driver' => 'local',
                'root' => $s3Root,
                'url' => 'https://media.example.com',
                'visibility' => 'public',
            ],
        ]);
    }

    public function testMediaUrlKeepsBundledAssetsOnTheAppHost()
    {
        $this->assertStringStartsWith(
            asset('assets/common/img/avatar/default.png'),
            media_url('assets/common/img/avatar/default.png')
        );
    }

    public function testBundledAssetUrlCarriesAContentVersion()
    {
        $path = 'assets/projects/es-portfolio.jpg';
        $version = substr(md5_file(public_path($path)), 0, 10);

        $this->assertSame(asset($path) . '?v=' . $version, media_url($path));
    }

    public function testMissingBundledAssetHasNoVersion()
    {
        $this->assertSame(asset('assets/missing/none.jpg'), media_url('assets/missing/none.jpg'));
    }

    public function testMediaUrlUsesConfiguredMediaDiskForNewRelativePaths()
    {
        Storage::disk('s3')->put('avatar/test.png', 'avatar');

        $this->assertSame(
            'https://media.example.com/avatar/test.png',
            media_url('avatar/test.png')
        );
    }

    public function testMediaUrlFallsBackToMediaDiskForLegacyStoragePaths()
    {
        Storage::disk('s3')->put('avatar/legacy.png', 'legacy');

        $this->assertSame(
            'https://media.example.com/avatar/legacy.png',
            media_url('storage/avatar/legacy.png')
        );
    }

    public function testDeleteResolvesLegacyStoragePathsAgainstTheMediaDisk()
    {
        Storage::disk('s3')->put('avatar/delete-me.png', 'delete');

        $this->assertTrue(MediaStorage::delete('storage/avatar/delete-me.png'));
        $this->assertFalse(Storage::disk('s3')->exists('avatar/delete-me.png'));
    }
}
