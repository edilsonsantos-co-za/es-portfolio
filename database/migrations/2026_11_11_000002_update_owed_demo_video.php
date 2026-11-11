<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateOwedDemoVideo extends Migration
{
    private const OLD = 'rwOyFtvEFLg';
    private const NEW = 'ZhHI00SAyu8';

    /** The Owed demo video was re-uploaded (full-stack intro); point the project's video and links at it. */
    public function up()
    {
        $this->swap(self::OLD, self::NEW);
    }

    public function down()
    {
        $this->swap(self::NEW, self::OLD);
    }

    private function swap(string $from, string $to): void
    {
        if (!Schema::hasTable('projects') || !Schema::hasColumn('projects', 'video_url')) {
            return;
        }
        DB::table('projects')
            ->where('video_url', 'like', "%{$from}%")
            ->orWhere('links', 'like', "%{$from}%")
            ->orWhere('details', 'like', "%{$from}%")
            ->get(['id', 'video_url', 'links', 'details'])
            ->each(fn ($p) => DB::table('projects')->where('id', $p->id)->update([
                'video_url' => $p->video_url === null ? null : str_replace($from, $to, $p->video_url),
                'links' => $p->links === null ? null : str_replace($from, $to, $p->links),
                'details' => $p->details === null ? null : str_replace($from, $to, $p->details),
                'updated_at' => now(),
            ]));
    }
}
