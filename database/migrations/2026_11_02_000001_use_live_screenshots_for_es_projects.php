<?php

use App\Console\Commands\SyncEsProjects;
use Illuminate\Database\Migrations\Migration;

class UseLiveScreenshotsForEsProjects extends Migration
{
    /** Swap the ES mark placeholder on the ES catalogue cards for the committed live screenshots. */
    public function up()
    {
        SyncEsProjects::applyScreenshots();
    }

    public function down()
    {
        // Data-only: the previous placeholder is not worth restoring.
    }
}
