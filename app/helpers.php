<?php

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