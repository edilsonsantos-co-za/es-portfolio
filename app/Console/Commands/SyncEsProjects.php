<?php

namespace App\Console\Commands;

use App\Models\Project;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncEsProjects extends Command
{
    protected $signature = 'es:projects:sync {--dry-run : Show intended updates without writing} {--developers-url= : Verified live developer docs URL} {--labs-url= : Verified live Labs URL}';

    protected $description = 'Add the ES launch catalog under Personal without resetting other portfolio data';

    public const PLACEHOLDER = 'assets/brand/es-mark.svg';

    /** Live screenshot committed at public/assets/projects/<slug>.jpg, if there is one. */
    public static function screenshotFor(string $title): ?string
    {
        $path = 'assets/projects/' . Str::slug($title) . '.jpg';

        return is_file(public_path($path)) ? $path : null;
    }

    /** Empty, or only the ES mark placeholder (as a thumbnail path or a JSON image list). */
    public static function isPlaceholder(?string $value): bool
    {
        return in_array($value, [null, '', '[]', self::PLACEHOLDER, json_encode([self::PLACEHOLDER]), '["' . self::PLACEHOLDER . '"]'], true);
    }

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
                // A captured live screenshot (see the Project screenshots workflow) replaces the placeholder mark,
                // never a custom image chosen in the admin.
                $image = self::screenshotFor($title) ?? self::PLACEHOLDER;
                $project->fill([
                    'title' => $title,
                    'categories' => json_encode($categories, JSON_THROW_ON_ERROR),
                    'link' => $link,
                    'details' => $project->details ?: $details,
                    'thumbnail' => self::isPlaceholder($project->thumbnail) ? $image : $project->thumbnail,
                    'images' => self::isPlaceholder($project->images) ? json_encode([$image]) : $project->images,
                ])->save();
            }
        });

        return self::SUCCESS;
    }
}
