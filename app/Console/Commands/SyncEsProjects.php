<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncEsProjects extends Command
{
    protected $signature = 'es:projects:sync {--dry-run : Show intended updates without writing} {--developers-url= : Verified live developer docs URL} {--labs-url= : Verified live Labs URL}';

    protected $description = 'Add the ES launch catalog under Personal without resetting other portfolio data';

    public function handle(): int
    {
        foreach (['developers-url', 'labs-url'] as $option) {
            $url = $this->option($option);
            if ($url && (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(parse_url($url, PHP_URL_SCHEME), ['https', 'http'], true))) {
                $this->error($option . ' must be a verified HTTP(S) URL.');
                return self::FAILURE;
            }
        }
        $catalog = [
            ['ES Portfolio', ['Portfolio', 'EdFolio'], 'https://edilsonsantos.co.za/', 'A self-hosted Laravel portfolio with project showcases, themes and SSO.'],
            ['ES Blog', ['EdFolio Blog'], 'https://blog.edilsonsantos.co.za/', 'A self-hosted Symfony editorial workspace for original articles, reviewed social drafts and opt-in newsletters.'],
            ['ES Journal', [], 'https://journal.edilsonsantos.co.za/', 'A BookStack-based private journal. The application is showcased here; journal content requires authentication and is never public.'],
            ['ES Developers', [], $this->option('developers-url') ?: null, 'Developer documentation for installing, configuring and contributing to the ES apps.' . ($this->option('developers-url') ? '' : ' Public documentation launch is being prepared.')],
            ['ES Labs', [], $this->option('labs-url') ?: null, 'A collection of experiments and prototypes.' . ($this->option('labs-url') ? '' : ' Public demos are being prepared; not every experiment is production-ready.')],
        ];
        DB::transaction(function () use ($catalog): void {
            foreach ($catalog as [$title, $aliases, $link, $details]) {
                $project = Project::whereIn('title', array_merge([$title], $aliases))->first();
                if (!$project && $link) {
                    $project = Project::where('link', $link)->first();
                }
                $this->line(($project ? 'Update: ' : 'Add: ') . $title . ($link ? ' (' . $link . ')' : ' (coming soon)'));
                if ($this->option('dry-run')) {
                    continue;
                }
                $project ??= new Project();
                $categories = json_decode($project->categories ?? '[]', true);
                $categories = is_array($categories) ? $categories : [];
                $categories = array_values(array_unique(array_merge($categories, ['Personal'])));
                $project->fill([
                    'title' => $title,
                    'categories' => json_encode($categories, JSON_THROW_ON_ERROR),
                    'link' => $link,
                    'details' => $project->details ?: $details,
                    'thumbnail' => $project->thumbnail ?: 'assets/brand/es-mark.svg',
                    'images' => $project->images ?: json_encode(['assets/brand/es-mark.svg']),
                ])->save();
            }
        });

        return self::SUCCESS;
    }
}
