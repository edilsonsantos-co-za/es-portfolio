<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Lists the frontend themes installed under resources/views/frontend/theme.
 * Atlas ships with the app; themes from the ES Marketplace store add a
 * <id>.blade.php view (plus public/assets/themes/<id>) next to it.
 */
class InstalledThemes
{
    public const DEFAULT = 'atlas';

    public function __construct(private ?string $viewPath = null)
    {
        $this->viewPath ??= resource_path('views/frontend/theme');
    }

    /**
     * @return array<int, array{id: string, title: string, image: string}>
     */
    public function all(): array
    {
        $ids = [];

        foreach (glob($this->viewPath . '/*.blade.php') ?: [] as $file) {
            $id = basename($file, '.blade.php');
            if (preg_match('/^[a-z0-9][a-z0-9-]*$/', $id)) {
                $ids[] = $id;
            }
        }

        sort($ids);
        // Keep the built-in theme first.
        usort($ids, fn ($a, $b) => ($b === self::DEFAULT) <=> ($a === self::DEFAULT));

        return array_map(fn ($id) => [
            'id' => $id,
            'title' => Str::headline($id),
            'image' => 'assets/common/img/templates/' . $id . '.png',
        ], $ids);
    }

    public function has(?string $id): bool
    {
        return is_string($id) && in_array($id, array_column($this->all(), 'id'), true);
    }

    /**
     * The view to render for a configured theme id, falling back to Atlas.
     */
    public function view(?string $id): string
    {
        return 'frontend.theme.' . ($this->has($id) ? $id : self::DEFAULT);
    }
}
