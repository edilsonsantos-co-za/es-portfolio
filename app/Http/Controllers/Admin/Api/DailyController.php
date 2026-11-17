<?php

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use CoreConstants;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * The dashboard's daily picture and quote.
 *
 * The picture is Wikimedia Commons' Picture of the Day: free to show with credit, and no API key. The
 * server asks once a day and caches the answer, so the browser never waits on a third party and a slow
 * or failed lookup only means a day without a picture (the tile falls back to its plain colour).
 */
class DailyController extends Controller
{
    public function show(): JsonResponse
    {
        $today = now()->format('Y-m-d');

        return response()->json([
            'message' => 'Data is fetched successfully',
            'payload' => ['date' => $today, 'picture' => $this->picture($today), 'quote' => self::quote()],
            'status' => CoreConstants::STATUS_CODE_SUCCESS,
        ]);
    }

    /** The same quote all day, a new one tomorrow. */
    public static function quote(?\DateTimeInterface $day = null): ?array
    {
        $quotes = config('daily_quotes', []);
        if (!$quotes) {
            return null;
        }
        [$text, $author] = $quotes[((int) ($day ?? now())->format('z')) % count($quotes)];

        return ['text' => $text, 'author' => $author];
    }

    private function picture(string $today): ?array
    {
        $key = 'dashboard-picture-' . $today;
        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached ?: null; // false = looked today and found nothing; do not ask again for an hour
        }

        $picture = $this->fetchPicture($today);
        Cache::put($key, $picture ?: false, $picture ? now()->addDay() : now()->addHour());

        return $picture;
    }

    private function fetchPicture(string $today): ?array
    {
        try {
            $response = Http::timeout(6)
                ->withHeaders(['User-Agent' => 'ES-Portfolio-Dashboard/1.0'])
                ->get('https://api.wikimedia.org/feed/v1/wikipedia/en/featured/' . str_replace('-', '/', $today));
            $image = $response->successful() ? $response->json('image') : null;
            $src = $image['thumbnail']['source'] ?? null;
            if (!$src || !str_starts_with($src, 'https://')) {
                return null;
            }
            // The feed's thumbnail is about 960px wide; ask Wikimedia for a 1280px one when the original is larger.
            if (($image['image']['width'] ?? 0) > 1280) {
                $src = preg_replace('#/\d+px-#', '/1280px-', $src, 1);
            }

            return [
                'url' => $src,
                'title' => trim(strip_tags($image['description']['text'] ?? '')) ?: preg_replace('/^File:|\.\w+$/', '', $image['title'] ?? ''),
                'artist' => trim(strip_tags($image['artist']['text'] ?? '')) ?: null,
                'license' => $image['license']['type'] ?? null,
                'licenseUrl' => $image['license']['url'] ?? null,
                'page' => $image['file_page'] ?? null,
            ];
        } catch (\Throwable $th) {
            Log::warning('Daily picture unavailable: ' . $th->getMessage());

            return null;
        }
    }
}
