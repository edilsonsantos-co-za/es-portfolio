<?php

namespace App\Http\Controllers\Frontend;

use App\Events\FrontendVisited;
use App\Http\Controllers\Controller;
use App\Services\InstalledThemes;
use App\Services\Contracts\FrontendInterface;
use Config;
use CoreConstants;
use Illuminate\Contracts\View\View;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FrontendController extends Controller
{
    /**
     * @var FrontendInterface
     */
    private $frontend;

    /**
     * Create a new instance
     *
     * @param FrontendInterface $frontend
     * @return void
     */
    public function __construct(FrontendInterface $frontend)
    {
        $this->frontend = $frontend;
    }

    /**
     * Handle request
     *
     * @param Request $request
     * @return View|Factory
     */
    public function index(Request $request)
    {
        return $this->render('home');
    }

    /**
     * Full project listing. Only Atlas has a dedicated page; other themes fall back to their #work section.
     *
     * @param Request $request
     * @return mixed
     */
    public function projects(Request $request)
    {
        return $this->render('projects');
    }

    /**
     * One portfolio section on its own page (experience, education, services or contact).
     *
     * @param Request $request
     * @param string $section
     * @return mixed
     */
    public function section(Request $request, string $section)
    {
        return $this->render($section);
    }

    /**
     * Render the active theme for a page ('home', 'projects' or a section page).
     *
     * @param string $page
     * @return mixed
     */
    private function render(string $page)
    {
        $data = $this->frontend->getAllData();

        if ($data['status'] !== CoreConstants::STATUS_CODE_SUCCESS) {
            return view('errors.404');
        } else {
            $data = $data['payload'];
        }

        $data['demoMode'] = Config::get('custom.demo_mode');

        if (empty($data['about'])) {
            return view('errors.custom', ['message' => 'Database has not propagated properly. Try running migration with seed.']);
        }

        if ((int)$data['portfolioConfig']['maintenanceMode'] === CoreConstants::TRUE) {
            return view('frontend.maintenance', $data);
        }

        $view = app(InstalledThemes::class)->view($data['portfolioConfig']['template'] ?? null);

        if ($page !== 'home') {
            // Only Atlas has dedicated pages; other themes, or a hidden/empty section, go to the one-page anchor.
            $key = ['projects' => 'projects', 'experience' => 'experiences', 'education' => 'education', 'services' => 'services', 'contact' => 'contact'][$page];
            $visible = !empty($data['portfolioConfig']['visibility'][$key]) && ($page === 'contact' || count($data[$key] ?? []));
            if ($view !== 'frontend.theme.' . InstalledThemes::DEFAULT || !$visible) {
                return redirect(url('/') . '#' . ($page === 'projects' ? 'work' : $page));
            }
        }

        $data['page'] = $page;

        return view($view, $data);
    }

    /**
     * A stable link to the current portrait, for the other ES apps (the blog's author card).
     * The stored file name changes on every upload, so they link here instead.
     */
    public function avatar()
    {
        $url = \App\Helpers\Utils::portraitUrl(\App\Models\About::query()->first());

        return redirect()->away($url)->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Handle pixel tracker
     *
     * @param Request $request
     * @return void
     */
    public function pixelTracker(Request $request)
    {
        if (!empty($request->event) && $request->event == 'page_visit') {
            FrontendVisited::dispatch($request->all());
        }
        
        header('Content-type: image/gif');
        echo base64_decode('R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==');
    }

}
