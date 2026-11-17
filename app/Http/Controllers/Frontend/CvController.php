<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\Contracts\FrontendInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use CoreConstants;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * The CV, generated on request from the content already in the admin: About, Experience, Education,
 * courses completed, Skills and Projects. Nothing is written by AI and nothing needs uploading: edit the
 * content and the next download reflects it. The "Show CV" switch in Portfolio > Config > Visibility
 * still hides it.
 */
class CvController extends Controller
{
    public function show(FrontendInterface $frontend): Response
    {
        $result = $frontend->getAllData();
        abort_unless($result['status'] === CoreConstants::STATUS_CODE_SUCCESS, 503);
        $data = $result['payload'];
        abort_unless(!empty($data['portfolioConfig']['visibility']['cv']) && !empty($data['about']), 404);

        $cv = self::content($data);
        $name = $cv['name'] ?: 'CV';

        return Pdf::loadView('frontend.cv', $cv)
            ->setPaper('a4')
            ->stream(Str::slug($name) . '-cv.pdf');
    }

    /** The CV's content, shaped for the template. Public so a test can check it without rendering a PDF. */
    public static function content(array $data): array
    {
        $about = $data['about'];
        $visibility = $data['portfolioConfig']['visibility'] ?? [];
        $shown = fn (string $key) => !empty($visibility[$key]);
        $json = fn ($value) => is_array($value) ? $value : (json_decode($value ?? '[]', true) ?: []);
        // Only the formatting tags the editor produces; anything else becomes plain text.
        $html = fn (?string $value) => trim(strip_tags((string) $value, '<p><ul><ol><li><strong><b><em><i><br>'));

        return [
            'name' => $about->name ?? '',
            'headline' => $json($about->taglines ?? null)[0] ?? null,
            'email' => $about->email ?? null,
            'address' => $about->address ?? null,
            'website' => url('/'),
            'links' => collect($json($about->social_links ?? null))
                ->filter(fn ($l) => !empty($l['link']) && str_starts_with($l['link'], 'http'))
                ->map(fn ($l) => ['title' => ucfirst($l['title'] ?? ''), 'url' => preg_replace('#^https?://(www\.)?#', '', rtrim($l['link'], '/'))])
                ->values()->all(),
            'summary' => $shown('about') ? $html($about->description ?? '') : '',
            'experiences' => $shown('experiences') ? collect($data['experiences'] ?? [])->sortByDesc('id')->map(fn ($e) => [
                'position' => $e->position, 'company' => $e->company, 'period' => $e->period, 'details' => $html($e->details),
            ])->values()->all() : [],
            'education' => $shown('education') ? collect($data['education'] ?? [])->sortByDesc('period')->map(fn ($e) => [
                'degree' => $e->degree, 'institution' => $e->institution, 'period' => $e->period,
                'department' => $e->department, 'grade' => $e->cgpa,
            ])->values()->all() : [],
            'courses' => $shown('education') ? collect($data['certificates'] ?? [])->sortByDesc('completed_on')->map(fn ($c) => [
                'title' => $c->title, 'issuer' => $c->issuer, 'date' => $c->completed_on?->format('M Y'),
            ])->values()->all() : [],
            'skills' => $shown('skills') ? collect($data['skills'] ?? [])->sortByDesc('proficiency')->pluck('name')
                // Skills stored in capitals (SYMFONY) read as shouting in a CV; short acronyms (PHP, AWS) stay.
                ->map(fn ($s) => strtoupper($s) === $s && strlen($s) > 4 ? Str::title(strtolower($s)) : $s)
                ->values()->all() : [],
            'projects' => $shown('projects') ? collect($data['projects'] ?? [])->map(fn ($p) => [
                'title' => $p->title,
                'summary' => Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $p->details))), 220),
                'link' => $p->link ? preg_replace('#^https?://(www\.)?#', '', rtrim($p->link, '/#')) : null,
            ])->values()->all() : [],
            'generated' => now()->format('j F Y'),
        ];
    }
}
