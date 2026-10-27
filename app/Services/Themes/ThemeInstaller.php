<?php

namespace App\Services\Themes;

use App\Services\InstalledThemes;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;

/**
 * Installs and removes verified theme packages under storage/app/themes/<id>/.
 *
 * Files are written by the installer (never extracted by zip), staged in a
 * temporary folder and swapped in with a rename, so a failed install leaves
 * the previous version untouched.
 */
class ThemeInstaller
{
    public function __construct(
        private ThemePackageVerifier $verifier,
        private InstalledThemes $themes,
        private Filesystem $fs,
    ) {
    }

    /**
     * @return array<string, mixed> the installed theme's manifest
     */
    public function install(string $zipPath, string $installedBy): array
    {
        ['manifest' => $manifest, 'files' => $files, 'signed' => $signed] = $this->verifier->verify($zipPath);

        $root = $this->themes->installedPath();
        $this->fs->ensureDirectoryExists($root, 0775);
        $stage = $root . '/.stage-' . bin2hex(random_bytes(6));

        try {
            foreach ($files as $path => $data) {
                $this->fs->ensureDirectoryExists(dirname($stage . '/' . $path), 0775);
                $this->fs->put($stage . '/' . $path, $data);
            }
            $this->fs->put($stage . '/manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
            $this->fs->put($stage . '/.installed.json', json_encode([
                'signed' => $signed,
                'installedAt' => now()->toAtomString(),
                'installedBy' => $installedBy,
            ], JSON_PRETTY_PRINT) . "\n");

            $target = $root . '/' . $manifest['id'];
            $old = null;
            if (is_dir($target)) {
                $old = $root . '/.old-' . bin2hex(random_bytes(6));
                rename($target, $old);
            }
            rename($stage, $target);
            if ($old !== null) {
                $this->fs->deleteDirectory($old);
            }
        } finally {
            if (is_dir($stage)) {
                $this->fs->deleteDirectory($stage);
            }
        }

        Log::notice('Theme installed', ['id' => $manifest['id'], 'version' => $manifest['version'], 'by' => $installedBy, 'signed' => $signed]);

        return $manifest;
    }

    public function remove(string $id, string $removedBy): void
    {
        if (!$this->themes->isInstalledPackage($id)) {
            throw new ThemePackageException('That theme is not installed from the store.');
        }

        $this->fs->deleteDirectory($this->themes->installedPath() . '/' . $id);
        Log::notice('Theme removed', ['id' => $id, 'by' => $removedBy]);
    }
}
