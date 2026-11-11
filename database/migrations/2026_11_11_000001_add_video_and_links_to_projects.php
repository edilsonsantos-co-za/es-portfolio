<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddVideoAndLinksToProjects extends Migration
{
    private const OWED_LINK = 'https://owed.edilsonsantos.co.za/';

    /** A project can show a video (YouTube URL) and extra links (label + URL); Owed gets its demo video, gallery and links. */
    public function up()
    {
        if (!Schema::hasTable('projects')) {
            return;
        }
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'video_url')) {
                $table->text('video_url')->nullable()->after('link');
            }
            if (!Schema::hasColumn('projects', 'links')) {
                $table->longText('links')->nullable()->after('video_url');
            }
        });

        $owed = DB::table('projects')->where('title', 'Owed')->orWhere('link', self::OWED_LINK)->first();
        if (!$owed) {
            return;
        }
        $images = json_encode([
            'assets/projects/owed.jpg',
            'assets/projects/owed-overview.jpg',
            'assets/projects/owed-approvals.jpg',
            'assets/projects/owed-clients.jpg',
            'assets/projects/owed-walkthrough.jpg',
        ]);
        $row = [
            'images' => $images,
            'video_url' => $owed->video_url ?? null ?: 'https://www.youtube.com/watch?v=ZhHI00SAyu8',
            'links' => $owed->links ?? null ?: json_encode([
                ['label' => 'Devpost submission', 'url' => 'https://devpost.com/software/owed-mi23or'],
                ['label' => 'Source code (MIT)', 'url' => 'https://github.com/edilsonsantos-co-za/owed'],
                ['label' => 'Demo video', 'url' => 'https://www.youtube.com/watch?v=ZhHI00SAyu8'],
            ]),
            'details' => "My entry to the PayPal AI Hackathon (Oct–Nov 2026).\n\n"
                . "Owed is an AI collections copilot for freelancers and small studios who bill through PayPal. It reads your PayPal invoices, learns how each client actually pays (average days late, on-time rate, a risk score) and forecasts when the cash will really land. Claude agents write a morning brief and draft the right reminder for each overdue invoice: friendly, firm or final.\n\n"
                . "Agents can draft but never send: every reminder waits in an approval queue, and only a signed-in person pressing Approve & send reaches PayPal. An audit trail records every draft, approval and PayPal call.\n\n"
                . "Built with Next.js, AG Studio dashboards and its Agent Framework (with a Claude adapter I wrote), PayPal Invoicing v2, Transaction Search and the PayPal Agent Toolkit, and sign-in through a shared Logto SSO. The demo video is recorded by a script from the real app.",
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('projects', 'images_json')) {
            $row['images_json'] = $images;
        }
        DB::table('projects')->where('id', $owed->id)->update($row);
    }

    public function down()
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'links')) {
                $table->dropColumn('links');
            }
            if (Schema::hasColumn('projects', 'video_url')) {
                $table->dropColumn('video_url');
            }
        });
    }
}
