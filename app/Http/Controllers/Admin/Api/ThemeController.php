<?php

namespace App\Http\Controllers\Admin\Api;

use App\Helpers\CoreConstants;
use App\Http\Controllers\Controller;
use App\Services\Contracts\PortfolioConfigInterface;
use App\Services\InstalledThemes;
use App\Services\Themes\ThemeInstaller;
use App\Services\Themes\ThemePackageException;
use App\Services\Themes\ThemeStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Portfolio -> Themes: install ES Marketplace themes from the store or from a
 * downloaded package, switch between them, and remove them.
 */
class ThemeController extends Controller
{
    public function __construct(
        private InstalledThemes $themes,
        private ThemeInstaller $installer,
        private ThemeStore $store,
        private PortfolioConfigInterface $portfolioConfig,
    ) {
    }

    public function index(): JsonResponse
    {
        $installed = $this->themes->all();
        $versions = array_column($installed, 'version', 'id');

        $storeThemes = [];
        $storeError = null;
        try {
            foreach ($this->store->themes() as $theme) {
                $current = $versions[$theme['id']] ?? null;
                $theme['state'] = !array_key_exists($theme['id'], $versions) ? 'new'
                    : ($current !== null && version_compare($theme['version'], $current, '>') ? 'update' : 'installed');
                $storeThemes[] = $theme;
            }
        } catch (\Throwable $e) {
            Log::warning('Theme store unavailable: ' . $e->getMessage());
            $storeError = 'The ES Marketplace store could not be reached. You can still upload a downloaded package.';
        }

        return $this->respond('Themes', [
            'installed' => $installed,
            'active' => $this->activeTheme(),
            'store' => $storeThemes,
            'storeError' => $storeError,
            'storeUrl' => rtrim((string) config('custom.store_url'), '/'),
        ]);
    }

    public function install(Request $request, string $id): JsonResponse
    {
        $file = null;
        try {
            $file = $this->store->download($id);

            return $this->finishInstall($file, $request->boolean('activate'));
        } catch (ThemePackageException $e) {
            return $this->respond($e->getMessage(), null, CoreConstants::STATUS_CODE_BAD_REQUEST);
        } catch (\Throwable $e) {
            Log::error('Theme install failed', ['id' => $id, 'error' => $e->getMessage()]);

            return $this->respond('The theme could not be installed. Check the logs for details.', null, CoreConstants::STATUS_CODE_ERROR);
        } finally {
            if ($file !== null) {
                @unlink($file);
            }
        }
    }

    public function upload(Request $request): JsonResponse
    {
        $upload = $request->file('package');
        if ($upload === null || !$upload->isValid()) {
            return $this->respond('Choose a theme package (.zip) downloaded from the ES Marketplace store.', null, CoreConstants::STATUS_CODE_BAD_REQUEST);
        }

        try {
            return $this->finishInstall($upload->getPathname(), $request->boolean('activate'));
        } catch (ThemePackageException $e) {
            return $this->respond($e->getMessage(), null, CoreConstants::STATUS_CODE_BAD_REQUEST);
        } catch (\Throwable $e) {
            Log::error('Theme upload failed', ['error' => $e->getMessage()]);

            return $this->respond('The theme could not be installed. Check the logs for details.', null, CoreConstants::STATUS_CODE_ERROR);
        }
    }

    public function activate(string $id): JsonResponse
    {
        $theme = $this->themes->get($id);
        if ($theme === null) {
            return $this->respond('That theme is not installed.', null, CoreConstants::STATUS_CODE_BAD_REQUEST);
        }

        $this->setActiveTheme($id);

        return $this->respond($theme['title'] . ' is now the portfolio theme.', ['active' => $id]);
    }

    public function destroy(string $id): JsonResponse
    {
        try {
            $title = $this->themes->get($id)['title'] ?? $id;
            if ($this->activeTheme() === $id) {
                $this->setActiveTheme(InstalledThemes::DEFAULT);
            }
            $this->installer->remove($id, $this->who());

            return $this->respond($title . ' was removed.', ['active' => $this->activeTheme()]);
        } catch (ThemePackageException $e) {
            return $this->respond($e->getMessage(), null, CoreConstants::STATUS_CODE_BAD_REQUEST);
        }
    }

    private function finishInstall(string $path, bool $activate): JsonResponse
    {
        $manifest = $this->installer->install($path, $this->who());
        if ($activate) {
            $this->setActiveTheme($manifest['id']);
        }

        return $this->respond(
            sprintf('%s %s installed%s.', $manifest['name'], $manifest['version'], $activate ? ' and activated' : ''),
            ['id' => $manifest['id'], 'active' => $this->activeTheme()]
        );
    }

    private function activeTheme(): string
    {
        $config = $this->portfolioConfig->getConfigByKey(CoreConstants::PORTFOLIO_CONFIG__TEMPLATE, ['setting_value']);
        $value = $config['payload']['setting_value'] ?? null;

        return $this->themes->has($value) ? $value : InstalledThemes::DEFAULT;
    }

    private function setActiveTheme(string $id): void
    {
        $this->portfolioConfig->insertOrUpdate(['setting_key' => CoreConstants::PORTFOLIO_CONFIG__TEMPLATE, 'setting_value' => $id]);
    }

    private function who(): string
    {
        $user = auth()->user();

        return $user?->email ?? 'admin';
    }

    private function respond(string $message, mixed $payload = null, int $status = CoreConstants::STATUS_CODE_SUCCESS): JsonResponse
    {
        return response()->json(['message' => $message, 'payload' => $payload, 'status' => $status], $status);
    }
}
