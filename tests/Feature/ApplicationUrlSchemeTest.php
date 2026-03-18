<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ApplicationUrlSchemeTest extends TestCase
{
    protected function tearDown(): void
    {
        URL::forceScheme(null);
        URL::forceRootUrl(null);

        parent::tearDown();
    }

    public function testAssetUrlsStayHttpWhenHttpsIsNotForced(): void
    {
        $this->configureUrlGeneration('http://example.test', false);

        $rendered = Blade::render("{{ asset('assets/common/app.css') }}");

        $this->assertSame('http://example.test/assets/common/app.css', trim($rendered));
    }

    public function testAssetUrlsCanBeForcedToHttpsExplicitly(): void
    {
        $this->configureUrlGeneration('http://example.test', true);

        $rendered = Blade::render("{{ asset('assets/common/app.css') }}");

        $this->assertSame('https://example.test/assets/common/app.css', trim($rendered));
    }

    private function configureUrlGeneration(string $url, bool $forceHttps): void
    {
        config([
            'app.url' => $url,
            'app.asset_url' => null,
            'app.force_https' => $forceHttps,
        ]);

        $request = Request::create($url . '/', 'GET');

        $this->app->instance('request', $request);
        $this->app['url']->setRequest($request);

        (new AppServiceProvider($this->app))->boot();
    }
}
