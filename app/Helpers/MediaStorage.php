<?php

namespace App\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaStorage
{
    /**
     * Get the configured media disk.
     *
     * @return string
     */
    public static function diskName()
    {
        return config('filesystems.media_disk', 'public');
    }

    /**
     * Store an uploaded media file on the configured media disk.
     *
     * @param UploadedFile $file
     * @param string $directory
     * @param string $defaultExtension
     * @return string|false
     */
    public static function storeUploadedFile(UploadedFile $file, string $directory, string $defaultExtension = 'png')
    {
        $disk = Storage::disk(self::diskName());

        if (!$disk->exists($directory)) {
            $disk->makeDirectory($directory);
        }

        $extension = $file->extension() ?: $defaultExtension;
        $fileName = Str::random(10).'_'.time().'.'.$extension;

        $result = $disk->putFileAs($directory, $file, $fileName);

        if ($result === false) {
            Log::error("MediaStorage: failed to store file in '{$directory}/{$fileName}'");
        }

        return $result;
    }

    /**
     * Resolve a media reference into a public URL.
     *
     * @param string|null $path
     * @return string
     */
    public static function url($path)
    {
        if (!$path) {
            return '';
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        if (Str::startsWith($path, 'assets/')) {
            return self::versionedAsset($path);
        }

        if (Str::startsWith($path, 'storage/')) {
            $relativePath = Str::after($path, 'storage/');

            foreach (self::legacyDisks() as $disk) {
                if (Storage::disk($disk)->exists($relativePath)) {
                    return self::diskUrl($disk, $relativePath);
                }
            }

            return asset($path);
        }

        return self::diskUrl(self::diskName(), ltrim($path, '/'));
    }

    /**
     * Bundled asset URL with a content-hash query (?v=), so a file replaced in place under the same name
     * (e.g. refreshed project screenshots) gets a new URL past browser, proxy and CDN caches.
     *
     * @param string $path
     * @return string
     */
    public static function versionedAsset(string $path)
    {
        static $versions = [];

        $file = public_path($path);
        if (!array_key_exists($file, $versions)) {
            $hash = is_file($file) ? @md5_file($file) : false;
            $versions[$file] = $hash ? substr($hash, 0, 10) : null;
        }

        return asset($path) . ($versions[$file] ? '?v=' . $versions[$file] : '');
    }

    /**
     * Check if a managed media reference exists.
     *
     * @param string|null $path
     * @return bool
     */
    public static function exists($path)
    {
        $resolvedPath = self::resolveManagedPath($path);

        if (!$resolvedPath) {
            return false;
        }

        return Storage::disk($resolvedPath['disk'])->exists($resolvedPath['path']);
    }

    /**
     * Delete a managed media reference.
     *
     * @param string|null $path
     * @return bool
     */
    public static function delete($path)
    {
        $resolvedPath = self::resolveManagedPath($path);

        if (!$resolvedPath) {
            return false;
        }

        return Storage::disk($resolvedPath['disk'])->delete($resolvedPath['path']);
    }

    /**
     * Resolve a stored path to the disk/path pair that manages it.
     *
     * @param string|null $path
     * @return array|null
     */
    private static function resolveManagedPath($path)
    {
        if (!$path || filter_var($path, FILTER_VALIDATE_URL) || Str::startsWith($path, 'assets/')) {
            return null;
        }

        if (Str::startsWith($path, 'storage/')) {
            $relativePath = Str::after($path, 'storage/');

            foreach (self::legacyDisks() as $disk) {
                if (Storage::disk($disk)->exists($relativePath)) {
                    return [
                        'disk' => $disk,
                        'path' => $relativePath,
                    ];
                }
            }

            return [
                'disk' => 'public',
                'path' => $relativePath,
            ];
        }

        return [
            'disk' => self::diskName(),
            'path' => ltrim($path, '/'),
        ];
    }

    /**
     * Build a public URL for a path on a specific disk.
     *
     * @param string $disk
     * @param string $path
     * @return string
     */
    private static function diskUrl(string $disk, string $path)
    {
        $url = Storage::disk($disk)->url($path);

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        return asset(ltrim($url, '/'));
    }

    /**
     * Candidate disks for resolving legacy storage references.
     *
     * @return array
     */
    private static function legacyDisks()
    {
        $disks = ['public'];
        $mediaDisk = self::diskName();

        if ($mediaDisk !== 'public') {
            $disks[] = $mediaDisk;
        }

        return array_values(array_unique($disks));
    }
}
