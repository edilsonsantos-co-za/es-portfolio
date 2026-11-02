<?php

namespace App\Helpers;

/**
 * View data for the Atlas frontend theme.
 */
class ThemeContext
{
    private const SOCIAL_LABELS = [
        'linkedin' => 'LinkedIn',
        'github' => 'GitHub',
        'youtube' => 'YouTube',
        'tiktok' => 'TikTok',
    ];

    /**
     * Build the derived values a theme needs from the frontend payload
     *
     * @param object $about
     * @param iterable $experiences
     * @return array{taglines: array, socials: array, blogUrl: ?string, developersUrl: ?string, storeUrl: ?string, upworkUrl: ?string, supportUrl: ?string, coffeeUrl: ?string, years: ?int, availability: ?string, timezone: string, timezoneCity: string, utcOffset: string, timeNote: ?string}
     */
    public static function make($about, $experiences): array
    {
        $years = null;
        $periods = collect($experiences)->pluck('period')->implode(' ');
        if (preg_match_all('/\b(19|20)\d{2}\b/', $periods, $matches) && !empty($matches[0])) {
            $years = (int) date('Y') - (int) min($matches[0]);
        }

        return [
            'taglines' => $about->taglines ? array_values((array) safe_json_decode($about->taglines)) : [],
            'socials' => $about->social_links ? (array) safe_json_decode($about->social_links) : [],
            'blogUrl' => config('custom.blog_url'),
            'developersUrl' => self::publicHttpUrl(config('custom.developers_url')),
            'storeUrl' => config('custom.store_url'),
            'upworkUrl' => self::publicHttpUrl(config('custom.upwork_url')),
            'supportUrl' => self::publicHttpUrl(config('custom.support_url')),
            'coffeeUrl' => self::publicHttpUrl(config('custom.coffee_url')),
            'years' => $years,
            'availability' => config('custom.availability') ?: null,
            'timeNote' => config('custom.time_note') ?: null,
        ] + self::timezone((string) config('custom.timezone', 'UTC'));
    }

    private static function publicHttpUrl(?string $url): ?string
    {
        $url = trim((string) $url);
        $parts = parse_url($url);

        if (
            $url === ''
            || filter_var($url, FILTER_VALIDATE_URL) === false
            || !is_array($parts)
            || !isset($parts['host'], $parts['scheme'])
            || isset($parts['user'])
            || isset($parts['pass'])
            || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)
        ) {
            return null;
        }

        return $url;
    }

    /**
     * Time zone details for the local-time display, e.g. "Africa/Johannesburg" gives
     * the city "Johannesburg" and the offset "UTC+2"
     *
     * @param string $name
     * @return array{timezone: string, timezoneCity: string, utcOffset: string}
     */
    private static function timezone(string $name): array
    {
        try {
            $zone = new \DateTimeZone($name);
        } catch (\Exception $e) {
            $zone = new \DateTimeZone('UTC');
        }

        $seconds = $zone->getOffset(new \DateTimeImmutable('now', $zone));
        $offset = 'UTC' . ($seconds === 0 ? '' : ($seconds > 0 ? '+' : '-') . rtrim(rtrim(number_format(abs($seconds) / 3600, 1), '0'), '.'));
        $city = str_replace('_', ' ', substr(strrchr('/' . $zone->getName(), '/'), 1));

        return [
            'timezone' => $zone->getName(),
            'timezoneCity' => $city,
            'utcOffset' => $offset,
        ];
    }

    /**
     * Display name for a social network title such as "linkedin"
     *
     * @param string $title
     * @return string
     */
    public static function socialLabel(string $title): string
    {
        return self::SOCIAL_LABELS[strtolower($title)] ?? ucfirst($title);
    }
}
