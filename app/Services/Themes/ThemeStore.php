<?php

namespace App\Services\Themes;

use Illuminate\Support\Facades\Http;

/**
 * Reads the ES Marketplace catalogue and downloads portfolio theme packages.
 *
 * Requests only ever go to THEME_STORE_URL; catalogue download paths must be
 * relative to it, and each download is checked against the catalogue's SHA-256
 * before the installer verifies its signature.
 */
class ThemeStore
{
    public function __construct(private string $storeUrl)
    {
    }

    /**
     * Portfolio themes that install from the admin.
     *
     * @return list<array<string, mixed>>
     */
    public function themes(): array
    {
        $themes = [];
        foreach ($this->catalogue() as $item) {
            $themes[] = [
                'id' => (string) $item['id'],
                'name' => (string) ($item['name'] ?? $item['id']),
                'version' => (string) ($item['version'] ?? ''),
                'summary' => (string) ($item['summary'] ?? ''),
                'author' => (string) ($item['author'] ?? ''),
                'license' => (string) ($item['license'] ?? ''),
                'preview' => is_string($item['preview'] ?? null) ? $item['preview'] : null,
                'repository' => is_string($item['repository'] ?? null) ? $item['repository'] : null,
                'size' => (int) ($item['size'] ?? 0),
            ];
        }

        return $themes;
    }

    /**
     * Downloads a theme package to a temporary file and returns its path.
     */
    public function download(string $id): string
    {
        $item = collect($this->catalogue())->firstWhere('id', $id);
        if ($item === null || !is_string($item['download'] ?? null) || !is_string($item['sha256'] ?? null)) {
            throw new ThemePackageException('The store does not offer that theme for ES Portfolio.');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'theme-');
        $response = Http::timeout(60)->withOptions(['allow_redirects' => false, 'sink' => $tmp])->get($this->url($item['download']));

        if (!$response->successful() || filesize($tmp) > ThemePackageVerifier::MAX_PACKAGE_BYTES) {
            @unlink($tmp);
            throw new ThemePackageException('The store could not send that package. Try again later.');
        }
        if (!hash_equals($item['sha256'], (string) hash_file('sha256', $tmp))) {
            @unlink($tmp);
            throw new ThemePackageException('The download does not match the store catalogue. Try again later.');
        }

        return $tmp;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function catalogue(): array
    {
        $catalog = Http::timeout(8)->withOptions(['allow_redirects' => false])->get($this->url('catalog.json'))->throw()->json();

        return array_values(array_filter($catalog['items'] ?? [], fn ($item) => is_array($item)
            && ($item['app'] ?? null) === 'portfolio'
            && ($item['install'] ?? null) === 'admin'
            && is_string($item['download'] ?? null)
            && is_string($item['id'] ?? null)));
    }

    private function url(string $path): string
    {
        if (str_contains($path, '://') || str_starts_with($path, '/') || str_contains($path, '..') || str_contains($path, '\\')) {
            throw new ThemePackageException('The store catalogue has an invalid download path.');
        }

        return rtrim($this->storeUrl, '/') . '/' . $path;
    }
}
