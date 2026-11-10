<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddOwedProject extends Migration
{
    private const LINK = 'https://owed.edilsonsantos.co.za/';

    /** Owed (PayPal AI Hackathon 2026 entry) under Personal; skipped when a card for it already exists. */
    public function up()
    {
        if (!Schema::hasTable('projects')
            || DB::table('projects')->where('title', 'Owed')->orWhere('link', self::LINK)->exists()) {
            return;
        }
        $image = 'assets/projects/owed.jpg';
        $row = [
            'title' => 'Owed',
            'categories' => json_encode(['Personal']),
            'thumbnail' => $image,
            'images' => json_encode([$image]),
            'details' => 'My PayPal AI Hackathon 2026 entry. Owed is an AI collections copilot for freelancers and small studios who bill through PayPal: Claude agents read your invoices, judge how each client really pays and draft the right reminder, and nothing is sent until you approve it. Built with Next.js, AG Grid and AG Studio dashboards, PayPal Invoicing and the PayPal Agent Toolkit, with sign-in through a shared Logto SSO.',
            'link' => self::LINK,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('projects', 'images_json')) {
            $row['images_json'] = $row['images'];
        }
        DB::table('projects')->insert($row);
    }

    public function down()
    {
        DB::table('projects')->where('title', 'Owed')->where('link', self::LINK)->delete();
    }
}
