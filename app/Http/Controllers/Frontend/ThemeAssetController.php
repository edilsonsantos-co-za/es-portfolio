<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\InstalledThemes;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serves an installed theme's static files from storage/app/themes/<id>/assets/,
 * plus its preview image for the admin.
 */
class ThemeAssetController extends Controller
{
    private const TYPES = [
        'css' => 'text/css', 'js' => 'text/javascript', 'map' => 'application/json',
        'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif',
        'webp' => 'image/webp', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon',
        'woff' => 'font/woff', 'woff2' => 'font/woff2', 'ttf' => 'font/ttf', 'otf' => 'font/otf',
        'eot' => 'application/vnd.ms-fontobject',
    ];

    public function __invoke(InstalledThemes $themes, string $id, string $path)
    {
        $base = $themes->installedPath() . '/' . $id;
        $file = in_array($path, ['preview.jpg', 'preview.png'], true) ? $base . '/' . $path : $base . '/assets/' . $path;
        $type = self::TYPES[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? null;
        $real = realpath($file);
        $root = realpath($base);

        abort_if(
            $type === null || str_contains($path, '..') || $real === false || $root === false
                || !str_starts_with($real, $root . '/') || !is_file($real) || !$themes->isInstalledPackage($id),
            404
        );

        $response = new BinaryFileResponse($real, 200, ['Content-Type' => $type, 'X-Content-Type-Options' => 'nosniff'], true, null, true, true);
        $response->setPublic();
        $response->setMaxAge(3600);
        if ($type === 'image/svg+xml') {
            $response->headers->set('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; sandbox");
        }
        $response->isNotModified(request());

        return $response;
    }
}
