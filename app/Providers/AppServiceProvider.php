<?php

namespace App\Providers;

use App\Models\Experience;
use App\Services\AboutService;
use App\Services\AdminService;
use App\Services\ApplicationVersion;
use App\Services\Contracts\AboutInterface;
use App\Services\Contracts\AdminInterface;
use App\Services\Contracts\EducationInterface;
use App\Services\Contracts\ExperienceInterface;
use App\Services\Contracts\FrontendInterface;
use App\Services\Contracts\MessageInterface;
use App\Services\Contracts\PortfolioConfigInterface;
use App\Services\Contracts\ProjectInterface;
use App\Services\Contracts\ServiceInterface;
use App\Services\Contracts\SettingInterface;
use App\Services\Contracts\SkillInterface;
use App\Services\Contracts\VisitorInterface;
use App\Services\EducationService;
use App\Services\ExperienceService;
use App\Services\FrontendService;
use App\Services\InstalledThemes;
use App\Services\MessageService;
use App\Services\PortfolioConfigService;
use App\Services\ProjectService;
use App\Services\ServiceService;
use App\Services\SettingService;
use App\Services\SkillService;
use App\Services\Themes\ThemePackageVerifier;
use App\Services\Themes\ThemeStore;
use App\Services\VisitorService;
use App\Helpers\Utils;
use Config;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Schema;
use URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->isLocal()) {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }

        $this->app->bind(SettingInterface::class, SettingService::class);
        $this->app->bind(AboutInterface::class, AboutService::class);
        $this->app->bind(AdminInterface::class, AdminService::class);
        $this->app->bind(PortfolioConfigInterface::class, PortfolioConfigService::class);
        $this->app->bind(EducationInterface::class, EducationService::class);
        $this->app->bind(ExperienceInterface::class, ExperienceService::class);
        $this->app->bind(SkillInterface::class, SkillService::class);
        $this->app->bind(ProjectInterface::class, ProjectService::class);
        $this->app->bind(ServiceInterface::class, ServiceService::class);
        $this->app->bind(FrontendInterface::class, FrontendService::class);
        $this->app->bind(VisitorInterface::class, VisitorService::class);
        $this->app->bind(MessageInterface::class, MessageService::class);
        $this->app->singleton(ApplicationVersion::class, function () {
            return new ApplicationVersion(base_path('VERSION'));
        });
        $this->app->bind(ThemePackageVerifier::class, function ($app) {
            return new ThemePackageVerifier(
                $app->make(ApplicationVersion::class),
                config_path('theme-keys'),
                (bool) config('custom.themes_allow_unsigned'),
            );
        });
        $this->app->bind(ThemeStore::class, function () {
            return new ThemeStore((string) config('custom.theme_store_url'));
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Themes installed from the ES Marketplace: themes::<id>.views.theme
        View::addNamespace(InstalledThemes::VIEW_NAMESPACE, config('custom.themes_path'));

        if (Config::get('app.force_https')) {
            URL::forceScheme('https');
        } else {
            URL::forceScheme(null);
        }

        Schema::defaultStringLength(191);

        $applicationVersion = $this->app->make(ApplicationVersion::class);

        Config::set('app.version', $applicationVersion->version());
        Config::set('app.release_tag', $applicationVersion->releaseTag());

        view()->share('appVersion', $applicationVersion->version());
        view()->share('appReleaseTag', $applicationVersion->releaseTag());

    }
}
