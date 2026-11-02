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
     * Render the active theme for a page ('home' or 'projects').
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

        if ($page === 'projects') {
            $projectsVisible = !empty($data['portfolioConfig']['visibility']['projects']) && count($data['projects'] ?? []);
            if ($view !== 'frontend.theme.' . InstalledThemes::DEFAULT || !$projectsVisible) {
                return redirect(url('/') . '#work');
            }
        }

        $data['page'] = $page;

        return view($view, $data);
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
