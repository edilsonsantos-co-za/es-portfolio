<?php

use App\Helpers\MediaStorage;
use App\Helpers\Utils;

if (!function_exists('safe_json_decode')) {
    /**
     * Safely decode JSON data. If the input is already an array, return it as is.
     * If it's a string, attempt to JSON decode it.
     *
     * @param mixed $data
     * @param bool $assoc
     * @return mixed
     */
    function safe_json_decode($data, bool $assoc = false)
    {
        return Utils::safeJsonDecode($data, $assoc);
    }
}

if (!function_exists('media_url')) {
    /**
     * Resolve a stored media reference to a renderable URL.
     *
     * @param string|null $path
     * @return string
     */
    function media_url($path)
    {
        return MediaStorage::url($path);
    }
}

if (!function_exists('media_thumb')) {
    /**
     * A resized WebP copy of a stored image, at most $width px wide (the original if it can't be resized).
     *
     * @param string|null $path
     * @param int $width
     * @return string
     */
    function media_thumb($path, int $width = 960)
    {
        return MediaStorage::thumbnailUrl($path, $width);
    }
}
