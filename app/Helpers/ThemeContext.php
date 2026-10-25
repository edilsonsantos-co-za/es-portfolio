<?php

namespace App\Helpers;

/**
 * View data shared by the Orion family of frontend themes.
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
     * @return array{taglines: array, socials: array, blogUrl: ?string, years: ?int}
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
            'years' => $years,
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
