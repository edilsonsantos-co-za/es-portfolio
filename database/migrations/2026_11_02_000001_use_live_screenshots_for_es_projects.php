<?php

use App\Console\Commands\SyncEsProjects;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UseLiveScreenshotsForEsProjects extends Migration
{
    /** Swap the ES mark placeholder on the ES catalogue cards for the committed live screenshots. */
    public function up()
    {
        if (!Schema::hasTable('projects')) {
            return;
        }
        $hasImagesJson = Schema::hasColumn('projects', 'images_json');
        foreach (['ES Portfolio', 'ES Blog', 'ES Journal', 'ES Developers', 'ES Labs'] as $title) {
            $screenshot = SyncEsProjects::screenshotFor($title);
            if (!$screenshot) {
                continue;
            }
            foreach (DB::table('projects')->where('title', $title)->get() as $project) {
                $changes = [];
                if (SyncEsProjects::isPlaceholder($project->thumbnail)) {
                    $changes['thumbnail'] = $screenshot;
                }
                if (SyncEsProjects::isPlaceholder($project->images)) {
                    $changes['images'] = json_encode([$screenshot]);
                    if ($hasImagesJson) {
                        $changes['images_json'] = $changes['images'];
                    }
                }
                if ($changes) {
                    DB::table('projects')->where('id', $project->id)->update($changes);
                }
            }
        }
    }

    public function down()
    {
        // Data-only: the previous placeholder is not worth restoring.
    }
}
