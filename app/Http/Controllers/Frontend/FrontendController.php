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
     * One project's detail page (Atlas only; other themes fall back to their #work section).
     *
     * @param Request $request
     * @param string $project the title slug ("owed"); old "<id>-<slug>" links redirect to it
     * @return mixed
     */
    public function project(Request $request, string $project)
    {
        return $this->render('project', null, strtolower($project));
    }

    /**
     * Canonical detail URL for a project: /projects/<title-slug>, e.g. /projects/owed.
     * Built from the title only, so no database ids show in public links.
     *
     * @param object $project
     * @return string
     */
    public static function projectUrl($project): string
    {
        return route('frontend.project', self::projectSlug($project));
    }

    /**
     * @param object $project
     * @return string
     */
    public static function projectSlug($project): string
    {
        $slug = \Illuminate\Support\Str::slug((string) $project->title);

        return $slug !== '' ? $slug : 'project';
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
     * Render the active theme for a page ('home', 'projects', 'project' or a section page).
     *
     * @param string $page
     * @param int|null $projectId the project shown on the 'project' page
     * @param string|null $projectKey the requested "<id>-<slug>", to redirect stale slugs
     * @return mixed
     */
    private function render(string $page, ?int $projectId = null, ?string $projectKey = null)
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
            $key = ['projects' => 'projects', 'project' => 'projects', 'experience' => 'experiences', 'education' => 'education', 'services' => 'services', 'contact' => 'contact'][$page];
            $hasContent = count($data[$key] ?? [])
                || ($page === 'education' && (config('education.certificates') || config('education.plans')));
            $visible = !empty($data['portfolioConfig']['visibility'][$key]) && ($page === 'contact' || $hasContent);
            if ($view !== 'frontend.theme.' . InstalledThemes::DEFAULT || !$visible) {
                return redirect(url('/') . '#' . (in_array($page, ['projects', 'project'], true) ? 'work' : $page));
            }
        }

        if ($page === 'project') {
            $projects = collect($data['projects']);
            $project = $projects->first(fn ($p) => self::projectSlug($p) === $projectKey);
            if (!$project) {
                // Old links were /projects/<id>-<slug>: send them to the title URL for good
                $legacy = preg_match('/^(\d+)(?:-|$)/', (string) $projectKey, $m)
                    ? $projects->first(fn ($p) => (int) $p->id === (int) $m[1]) : null;
                if (!$legacy) {
                    abort(404);
                }

                return redirect(self::projectUrl($legacy), 301);
            }
            $data['project'] = $project;
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
        $data = $this->frontend->getAllData();
        $about = $data['status'] === CoreConstants::STATUS_CODE_SUCCESS ? ($data['payload']['about'] ?? null) : null;
        $url = \App\Helpers\Utils::portraitUrl($about);

        return redirect()->away($url)->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Handle pixel tracker
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function pixelTracker(Request $request)
    {
        if (!empty($request->event) && $request->event == 'page_visit') {
            FrontendVisited::dispatch($request->all());
        }
        
        return response(base64_decode('R0lGODlhAQABAIAAAP///////yH5BAEKAAEALAAAAAABAAEAAAICTAEAOw=='), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

}
