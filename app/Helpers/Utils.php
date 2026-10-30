<?php

namespace App\Helpers;

use CoreConstants;
use App\Models\Setting;
use App\Services\Contracts\AboutInterface;
use App\Services\Contracts\SettingInterface;

class Utils
{
    /**
     * Get the favicon path
     *
     * @return string
     */
    public static function getFavicon()
    {
        try {
            $settingService = resolve(SettingInterface::class);
            $result = $settingService->getSettingByKey(CoreConstants::SETTING__FAVICON, ['setting_value']);

            if ($result['status'] === CoreConstants::STATUS_CODE_SUCCESS) {
                return MediaStorage::url($result['payload']->setting_value);
            } else {
                return asset('assets/common/img/favicon/default.png');
            }
        } catch (\Throwable $th) {
            return asset('assets/common/img/favicon/default.png');
        }
    }
    
    /**
     * Return rgb value of a hex color value
     *
     * @param string $hex
     * @return string //r, g, b
     */
    public static function getRgbValue(string $hex)
    {
        $split = str_split(str_replace("#", "", $hex), 2);
        $r = hexdec($split[0]);
        $g = hexdec($split[1]);
        $b = hexdec($split[2]);
        $accentColorRGB = $r . ", " . $g . ", " . $b; //e.g. 255, 255, 255
        
        return $accentColorRGB;
    }

    /**
     * Get the logo path
     *
     * @return string
     */
    public static function getLogo()
    {
        try {
            $setting = resolve(SettingInterface::class);
            $result = $setting->getSettingByKey(CoreConstants::SETTING__LOGO, ['setting_value']);

            if ($result['status'] === CoreConstants::STATUS_CODE_SUCCESS) {
                return MediaStorage::url($result['payload']->setting_value);
            } else {
                return asset('assets/common/img/logo/default.png');
            }
        } catch (\Throwable $th) {
            return asset('assets/common/img/logo/default.png');
        }
    }

    public const DEFAULT_AVATAR = 'assets/common/img/avatar/default.png';

    /**
     * Gravatar for an email (identicon when the email has none), matching the other ES apps.
     *
     * @param string|null $email
     * @param int $size
     * @return string|null
     */
    public static function gravatarUrl(?string $email, int $size = 300)
    {
        $email = strtolower(trim((string) $email));

        return $email === '' ? null : sprintf('https://gravatar.com/avatar/%s?s=%d&d=identicon', hash('sha256', $email), $size);
    }

    /**
     * Portrait URL: the uploaded avatar, otherwise the Gravatar for the About email
     *
     * @param object|null $about
     * @param int $size
     * @return string|null
     */
    public static function portraitUrl($about, int $size = 600)
    {
        if ($about && $about->avatar && $about->avatar !== self::DEFAULT_AVATAR) {
            return media_url($about->avatar);
        }

        return self::gravatarUrl($about->email ?? null, $size);
    }

    /**
     * Get the avatar path
     *
     * @return string
     */
    public static function getAvatar()
    {
        try {
            $about = resolve(AboutInterface::class);
            $result = $about->getAll(['avatar']);

            if ($result['status'] === CoreConstants::STATUS_CODE_SUCCESS) {
                return MediaStorage::url($result['payload']->avatar);
            }

            return asset('assets/common/img/avatar/default.png');
        } catch (\Throwable $th) {
            return asset('assets/common/img/avatar/default.png');
        }
    }

    /**
     * Get the cover path
     *
     * @return string
     */
    public static function getCover()
    {
        try {
            $about = resolve(AboutInterface::class);
            $result = $about->getAll(['cover']);

            if ($result['status'] === CoreConstants::STATUS_CODE_SUCCESS) {
                return MediaStorage::url($result['payload']->cover);
            }

            return asset('assets/common/img/cover/default.png');
        } catch (\Throwable $th) {
            return asset('assets/common/img/cover/default.png');
        }
    }

    /**
     * Safely decode JSON data. If the input is already an array, return it as is.
     * If it's a string, attempt to JSON decode it.
     *
     * @param mixed $data
     * @param bool $assoc
     * @return mixed
     */
    public static function safeJsonDecode($data, bool $assoc = false)
    {
        if (is_array($data) || is_object($data)) {
            return $data;
        }

        if (is_string($data)) {
            $decoded = json_decode($data, $assoc);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $assoc ? [] : null;
    }
}
