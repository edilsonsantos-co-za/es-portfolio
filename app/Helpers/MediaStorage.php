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

    /**
     * URL of a resized WebP copy of an image (max $width px wide, aspect kept), made on first use and
     * stored under storage/thumbs/. The name hashes path + mtime, so a replaced image gets a new URL and
     * the copy can be cached forever. Falls back to the original when it can't be resized.
     */
    public static function thumbnailUrl($path, int $width = 960)
    {
        $original = self::url($path);
        $source = self::localFile($path);

        if (!$source || !function_exists('imagewebp') || !(imagetypes() & IMG_WEBP)) {
            return $original;
        }

        $name = 'thumbs/' . $width . '/' . substr(md5($path . '|' . filemtime($source)), 0, 16) . '.webp';
        $disk = Storage::disk('public');

        if ($disk->exists($name)) {
            return self::diskUrl('public', $name);
        }

        try {
            $size = @getimagesize($source);
            // Skip what isn't a raster image, is already small enough, or would exhaust memory (> 40 MP).
            if (!$size || $size[0] * $size[1] > 40000000 || ($size[0] <= $width && filesize($source) < 150000)) {
                return $original;
            }

            $image = @imagecreatefromstring(file_get_contents($source));
            if (!$image) {
                return $original;
            }

            $image = self::resize($image, $width);
            if (!$image) {
                return $original;
            }

            $target = $disk->path($name);
            @mkdir(dirname($target), 0775, true);
            $temp = $target . '.' . Str::random(6) . '.tmp';
            $ok = imagewebp($image, $temp, 80);

            if (!$ok || !@rename($temp, $target)) {
                @unlink($temp);
                return $original;
            }

            return self::diskUrl('public', $name);
        } catch (\Throwable $th) {
            Log::warning("MediaStorage: thumbnail for '{$path}' failed: " . $th->getMessage());
            return $original;
        }
    }

    /**
     * Absolute path of a media reference stored on this server, or null (remote URL, cloud disk, missing).
     */
    private static function localFile($path)
    {
        if (!$path || filter_var($path, FILTER_VALIDATE_URL)) {
            return null;
        }

        if (Str::startsWith($path, 'assets/')) {
            $file = public_path($path);
            return is_file($file) ? $file : null;
        }

        $managed = self::resolveManagedPath($path);
        if (!$managed || config("filesystems.disks.{$managed['disk']}.driver") !== 'local') {
            return null;
        }

        $file = Storage::disk($managed['disk'])->path($managed['path']);
        return is_file($file) ? $file : null;
    }

    /**
     * Copy of $image scaled down to at most $width px wide, as truecolor with alpha kept. Frees $image.
     */
    private static function resize($image, int $width)
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $tw = min($width, $w);
        $th = max(1, (int) round($h * $tw / $w));

        $out = imagecreatetruecolor($tw, $th);
        if (!$out) {
            return null;
        }

        imagealphablending($out, false);
        imagesavealpha($out, true);
        imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
        $ok = imagecopyresampled($out, $image, 0, 0, 0, 0, $tw, $th, $w, $h);

        return $ok ? $out : null;
    }
}
