<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Lists the frontend themes the portfolio can render.
 *
 * Atlas ships with the app as resources/views/frontend/theme/atlas.blade.php.
 * Themes installed from the ES Marketplace (Portfolio -> Themes) live in
 * storage/app/themes/<id>/ with a manifest.json, views/theme.blade.php and
 * assets/ (served by ThemeAssetController); that folder is a volume in
 * production so installs survive redeploys.
 */
class InstalledThemes
{
    public const DEFAULT = 'atlas';
    public const VIEW_NAMESPACE = 'themes';

    public function __construct(
        private ?string $viewPath = null,
        private ?string $installedPath = null,
    ) {
        $this->viewPath ??= resource_path('views/frontend/theme');
        $this->installedPath ??= config('custom.themes_path', storage_path('app/themes'));
    }

    public function installedPath(): string
    {
        return $this->installedPath;
    }

    /**
     * @return array<int, array{id: string, title: string, image: string, version: ?string, author: ?string, license: ?string, summary: ?string, repository: ?string, builtin: bool, signed: bool}>
     */
    public function all(): array
    {
        $themes = [];

        foreach (glob($this->viewPath . '/*.blade.php') ?: [] as $file) {
            $id = basename($file, '.blade.php');
            if (preg_match('/^[a-z0-9][a-z0-9-]*$/', $id)) {
                $themes[$id] = [
                    'id' => $id,
                    'title' => Str::headline($id),
                    'image' => 'assets/common/img/templates/' . $id . '.png',
                    'version' => null,
                    'author' => $id === self::DEFAULT ? 'Edilson Santos' : null,
                    'license' => 'MIT',
                    'summary' => $id === self::DEFAULT ? 'The built-in design.' : null,
                    'repository' => null,
                    'builtin' => true,
                    'signed' => true,
                ];
            }
        }

        foreach (glob($this->installedPath . '/*/manifest.json') ?: [] as $file) {
            $dir = dirname($file);
            $id = basename($dir);
            if (isset($themes[$id]) || !preg_match('/^[a-z0-9][a-z0-9-]*$/', $id) || !is_file($dir . '/views/theme.blade.php')) {
                continue;
            }

            $m = json_decode((string) file_get_contents($file), true) ?: [];
            $state = json_decode((string) @file_get_contents($dir . '/.installed.json'), true) ?: [];
            $themes[$id] = [
                'id' => $id,
                'title' => is_string($m['name'] ?? null) ? $m['name'] : Str::headline($id),
                'image' => is_file($dir . '/preview.png') ? 'themes/' . $id . '/preview.png' : 'assets/common/img/templates/' . self::DEFAULT . '.png',
                'version' => is_string($m['version'] ?? null) ? $m['version'] : null,
                'author' => is_string($m['author'] ?? null) ? $m['author'] : null,
                'license' => is_string($m['license'] ?? null) ? $m['license'] : null,
                'summary' => is_string($m['summary'] ?? null) ? $m['summary'] : null,
                'repository' => is_string($m['repository'] ?? null) ? $m['repository'] : null,
                'builtin' => false,
                'signed' => (bool) ($state['signed'] ?? false),
            ];
        }

        ksort($themes);
        // Keep the built-in theme first.
        uksort($themes, fn ($a, $b) => ($b === self::DEFAULT) <=> ($a === self::DEFAULT) ?: strcmp($a, $b));

        return array_values($themes);
    }

    public function get(?string $id): ?array
    {
        foreach ($this->all() as $theme) {
            if ($theme['id'] === $id) {
                return $theme;
            }
        }

        return null;
    }

    public function has(?string $id): bool
    {
        return is_string($id) && $this->get($id) !== null;
    }

    public function isInstalledPackage(?string $id): bool
    {
        $theme = $this->get($id);

        return $theme !== null && !$theme['builtin'];
    }

    /**
     * The view to render for a configured theme id, falling back to Atlas.
     */
    public function view(?string $id): string
    {
        $theme = $this->get($id);

        if ($theme === null) {
            return 'frontend.theme.' . self::DEFAULT;
        }

        return $theme['builtin'] ? 'frontend.theme.' . $id : self::VIEW_NAMESPACE . '::' . $id . '.views.theme';
    }
}
