<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Courses completed and learning plans shown on the Education page. Until 17 Nov 2026 these lived in
 * config/education.php; they move here so the admin can add and remove them. The entries in that file
 * are copied in once, when the table is created.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_items', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20)->index();          // certificate | plan
            $table->string('title');
            $table->string('issuer');
            $table->date('completed_on')->nullable();      // certificates only; plans never have a date
            $table->text('summary')->nullable();
            $table->string('image')->nullable();           // certificates only
            $table->unsignedInteger('image_width')->nullable();
            $table->unsignedInteger('image_height')->nullable();
            $table->string('verification_url')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        $now = now();
        foreach (config('education.certificates', []) as $i => $c) {
            DB::table('learning_items')->insert([
                'kind' => 'certificate', 'title' => $c['title'], 'issuer' => $c['issuer'],
                'completed_on' => $c['date'] ?? null, 'summary' => $c['summary'] ?? null,
                'image' => $c['image'] ?? null, 'image_width' => $c['width'] ?? null, 'image_height' => $c['height'] ?? null,
                'verification_url' => $c['verification_url'] ?? null, 'sort' => $i,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        foreach (config('education.plans', []) as $i => $p) {
            DB::table('learning_items')->insert([
                'kind' => 'plan', 'title' => $p['title'], 'issuer' => $p['issuer'],
                'summary' => $p['summary'] ?? null, 'sort' => $i,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_items');
    }
};
