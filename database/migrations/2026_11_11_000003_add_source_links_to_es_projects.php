<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddSourceLinksToEsProjects extends Migration
{
    /** Project title => [label, GitHub URL]; these three repos are public. */
    private const SOURCES = [
        'ES Portfolio' => ['Source code (MIT)', 'https://github.com/edilsonsantos-co-za/es-portfolio'],
        'ES Blog' => ['Source code (GPL-2.0)', 'https://github.com/edilsonsantos-co-za/es-blog'],
        'ES Developers' => ['Source code (MIT)', 'https://github.com/edilsonsantos-co-za/es-developers'],
    ];

    /** Adds a source-code link to each project, keeping any links already set in the admin. */
    public function up()
    {
        if (!Schema::hasTable('projects') || !Schema::hasColumn('projects', 'links')) {
            return;
        }
        foreach (self::SOURCES as $title => [$label, $url]) {
            $project = DB::table('projects')->where('title', $title)->first();
            if (!$project) {
                continue;
            }
            $links = json_decode((string) $project->links, true) ?: [];
            if (in_array($url, array_column($links, 'url'), true)) {
                continue;
            }
            $links[] = ['label' => $label, 'url' => $url];
            DB::table('projects')->where('id', $project->id)->update(['links' => json_encode($links), 'updated_at' => now()]);
        }
    }

    public function down()
    {
        if (!Schema::hasTable('projects') || !Schema::hasColumn('projects', 'links')) {
            return;
        }
        foreach (self::SOURCES as $title => [, $url]) {
            $project = DB::table('projects')->where('title', $title)->first();
            if (!$project) {
                continue;
            }
            $links = array_values(array_filter(json_decode((string) $project->links, true) ?: [], fn ($l) => ($l['url'] ?? null) !== $url));
            DB::table('projects')->where('id', $project->id)->update(['links' => $links ? json_encode($links) : null, 'updated_at' => now()]);
        }
    }
}
