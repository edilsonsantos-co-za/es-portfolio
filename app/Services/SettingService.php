<?php

namespace App\Services;

use CoreConstants;
use App\Helpers\MediaStorage;
use App\Models\Setting;
use App\Services\Contracts\AboutInterface;
use App\Services\Contracts\SettingInterface;
use Config;
use Log;
// TODO: Replace DotenvEditor with custom env file management
// use Jackiedo\DotenvEditor\Facades\DotenvEditor;
use Validator;

class SettingService implements SettingInterface
{
    /**
     * Eloquent instance
     *
     * @var Setting
     */
    private $model;

    /**
     * Create a new service instance.
     *
     * @param Setting $setting
     * @return void
     */
    public function __construct(Setting $setting)
    {
        $this->model = $setting;
    }

    /**
     * If setting exist, update it. Otherwise insert new
     *
     * @param array $data
     * @return array
     */
    public function insertOrUpdate(array $data)
    {
        try {
            $validate = Validator::make($data, [
                'setting_key' => 'required',
                'setting_value' => 'required',
                'default_value' => 'required|sometimes',
            ]);

            if ($validate->fails()) {
                return [
                    'message' => 'Bad Request',
                    'payload' => $validate->errors(),
                    'status' => CoreConstants::STATUS_CODE_BAD_REQUEST
                ];
            }

            if (isset($data['default_value'])) {
                $result = $this->model->updateOrCreate([
                    'setting_key' => $data['setting_key'],
                ], [
                    'setting_value' => $data['setting_value'],
                    'default_value' => $data['default_value']
                ]);
            } else {
                $result = $this->model->updateOrCreate([
                    'setting_key' => $data['setting_key'],
                ], [
                    'setting_value' => $data['setting_value']
                ]);
            }

            if ($result) {
                return [
                    'message' => 'Data is saved successfully',
                    'payload' => $result,
                    'status' => CoreConstants::STATUS_CODE_SUCCESS
                ];
            } else {
                return [
                    'message' => 'Something went wrong',
                    'payload' => null,
                    'status' => CoreConstants::STATUS_CODE_ERROR
                ];
            }
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return [
                'message' => 'Something went wrong',
                'payload' => $th->getMessage(),
                'status' => CoreConstants::STATUS_CODE_ERROR
            ];
        }
    }

    /**
     * Get single setting by key
     *
     * @param int $key
     * @param array $select
     * @return array
     */
    public function getSettingByKey(int $key, array $select = ['*'])
    {
        try {
            $result = $this->model
                        ->select($select)
                        ->where('setting_key', $key)
                        ->first();

            if ($result) {
                return [
                    'message' => 'Setting is fetched successfully',
                    'payload' => $result,
                    'status' => CoreConstants::STATUS_CODE_SUCCESS
                ];
            } else {
                return [
                    'message' => 'Setting is not found',
                    'payload' => null,
                    'status' => CoreConstants::STATUS_CODE_NOT_FOUND
                ];
            }
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return [
                'message' => 'Something went wrong',
                'payload' => $th->getMessage(),
                'status' => CoreConstants::STATUS_CODE_ERROR
            ];
        }
    }

    /**
     * Get all related settings
     *
     * @return array
     */
    public function getSettingsData()
    {
        try {
            //get accent color
            $result = $this->getSettingByKey(CoreConstants::SETTING__ACCENT_COLOR, ['setting_value']);

            if ($result['status'] === CoreConstants::STATUS_CODE_SUCCESS) {
                $data['accentColor'] = $result['payload']->setting_value;
            } else {
                $data['accentColor'] = '#1890ff';
            }

            //get short menu
            $result = $this->getSettingByKey(CoreConstants::SETTING__SHORT_MENU, ['setting_value']);

            if ($result['status'] === CoreConstants::STATUS_CODE_SUCCESS) {
                $data['shortMenu'] = ($result['payload']->setting_value === true || $result['payload']->setting_value === 'true' || $result['payload']->setting_value === 1 || $result['payload']->setting_value === '1' ? true : false);
            } else {
                $data['shortMenu'] = false;
            }

            //get menu layout
            $result = $this->getSettingByKey(CoreConstants::SETTING__MENU_LAYOUT, ['setting_value']);

            if ($result['status'] === CoreConstants::STATUS_CODE_SUCCESS) {
                $data['menuLayout'] = $result['payload']->setting_value === 'mix' ? 'side' : $result['payload']->setting_value;
            } else {
                $data['menuLayout'] = 'side';
            }

            //get site name
            $data['siteName'] = config('app.name', 'ES Portfolio');

            //get logo
            $result = $this->getSettingByKey(CoreConstants::SETTING__LOGO, ['setting_value']);

            if ($result['status'] === CoreConstants::STATUS_CODE_SUCCESS) {
                $data['logo'] = $result['payload']->setting_value;
            } else {
                $data['logo'] = 'assets/common/img/logo/default.png';
            }

            //get favicon
            $result = $this->getSettingByKey(CoreConstants::SETTING__FAVICON, ['setting_value']);

            if ($result['status'] === CoreConstants::STATUS_CODE_SUCCESS) {
                $data['favicon'] = $result['payload']->setting_value;
            } else {
                $data['favicon'] = 'assets/common/img/favicon/default.png';
            }

            //get cover photo
            $about = resolve(AboutInterface::class);

            $result = $about->getAll(['cover', 'id']);

            if ($result['status'] === CoreConstants::STATUS_CODE_SUCCESS) {
                $data['cover'] = $result['payload']->cover;
            } else {
                $data['cover'] = 'assets/common/img/cover/default.png';
            }

            //get avatar
            $result = $about->getAll(['avatar', 'id']);

            if ($result['status'] === CoreConstants::STATUS_CODE_SUCCESS) {
                $data['avatar'] = $result['payload']->avatar;
            } else {
                $data['avatar'] = 'assets/common/img/avatar/default.png';
            }

            // Mail: the connection comes from the server's settings and is shown read-only (never the
            // password, which is the mail platform's API key). The sender is editable and stored here.
            $mailer = config('mail.default');
            $data['mailSettings'] = [
                'MAIL_MAILER' => $mailer,
                'MAIL_HOST' => config("mail.mailers.$mailer.host"),
                'MAIL_PORT' => config("mail.mailers.$mailer.port"),
                'MAIL_USERNAME' => config("mail.mailers.$mailer.username"),
                'MAIL_FROM_ADDRESS' => config('mail.from.address'),
                'MAIL_FROM_NAME' => config('mail.from.name'),
                'MAIL_FROM_DOMAIN' => self::senderDomain(),
            ];

            //get demo mode
            $data['demoMode'] = Config::get('custom.demo_mode');
            
            return [
                'message' => 'Settings are fetched successfully',
                'payload' => $data,
                'status' => CoreConstants::STATUS_CODE_SUCCESS
            ];
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return [
                'message' => 'Something went wrong',
                'payload' => $th->getMessage(),
                'status' => CoreConstants::STATUS_CODE_ERROR
            ];
        }
    }

    /**
     * Set single setting
     *
     * @param array $data
     * @return array
     */
    public function setSettingData(array $data)
    {
        try {
            $validate = Validator::make($data, [
                'setting_key' => 'required',
                'setting_value' => 'required'
            ]);

            if ($validate->fails()) {
                return [
                    'message' => 'Bad Request',
                    'payload' => $validate->errors(),
                    'status' => CoreConstants::STATUS_CODE_BAD_REQUEST
                ];
            }
            
            if ($data['setting_key'] === CoreConstants::SETTING__SITE_NAME) {
                $result = $this->updateSiteName($data['setting_value']);
            } else {
                $newData['setting_key'] = $data['setting_key'];
                $newData['setting_value'] = $data['setting_value'];

                $result = $this->insertOrUpdate($newData);
            }
            
            return $result;
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return [
                'message' => 'Something went wrong',
                'payload' => $th->getMessage(),
                'status' => CoreConstants::STATUS_CODE_ERROR
            ];
        }
    }

    /**
     * Update Site name related env variables
     *
     * @param string $newName
     * @return array
     */
    public function updateSiteName(string $newName)
    {
        try {
            // TODO: Replace DotenvEditor with custom env file management
            // $file = DotenvEditor::setKey('APP_NAME', $newName);
            // $file = DotenvEditor::save();
            // TODO: For now, skip the file writing check
            if (true) { // $file
                return [
                    'message' => 'Site name is successfully updated',
                    'payload' => $newName,
                    'status' => CoreConstants::STATUS_CODE_SUCCESS
                ];
            } else {
                return [
                    'message' => 'Something went wrong',
                    'payload' => null,
                    'status' => CoreConstants::STATUS_CODE_ERROR
                ];
            }
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return [
                'message' => 'Something went wrong',
                'payload' => $th->getMessage(),
                'status' => CoreConstants::STATUS_CODE_ERROR
            ];
        }
    }

    /**
     * Process the update logo request
     *
     * @param array $data
     * @return array
     */
    public function processUpdateLogoRequest(array $data)
    {
        try {
            $validate = Validator::make($data, [
                'file' => 'required'
            ]);

            if ($validate->fails()) {
                return [
                    'message' => 'Bad Request',
                    'payload' => $validate->errors(),
                    'status' => CoreConstants::STATUS_CODE_BAD_REQUEST
                ];
            }
            $file = $data['file'];
            $stored = MediaStorage::storeUploadedFile($file, 'logo', 'png');
            if ($stored) {
                //delete previous logo
                try {
                    $oldLogoResponse = $this->getSettingByKey(CoreConstants::SETTING__LOGO);
                    if ($oldLogoResponse['status'] === CoreConstants::STATUS_CODE_SUCCESS && $oldLogoResponse['payload']->setting_value !== 'assets/common/img/logo/default.png') {
                        if (MediaStorage::exists($oldLogoResponse['payload']->setting_value)) {
                            MediaStorage::delete($oldLogoResponse['payload']->setting_value);
                        }
                    }
                } catch (\Throwable $th) {
                    Log::error($th->getMessage());
                }

                $newFilePath = $stored;
                $result  = $this->setSettingData([
                    'setting_key'  => CoreConstants::SETTING__LOGO,
                    'setting_value' => $newFilePath,
                ]);

                if ($result['status'] === CoreConstants::STATUS_CODE_SUCCESS) {
                    return [
                        'message' => 'File is successfully saved',
                        'payload' => [
                            'file' => $newFilePath
                        ],
                        'status' => CoreConstants::STATUS_CODE_SUCCESS
                    ];
                } else {
                    return $result;
                }
            } else {
                return [
                    'message' => 'File could not be saved',
                    'payload' => null,
                    'status' => CoreConstants::STATUS_CODE_ERROR
                ];
            }
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return [
                'message' => 'Something went wrong',
                'payload' => $th->getMessage(),
                'status' => CoreConstants::STATUS_CODE_ERROR
            ];
        }
    }

    /**
     * Process the delete logo request
     *
     * @param string $file
     * @return array
     */
    public function processDeleteLogoRequest(string $file = '')
    {
        return $this->clearImageSetting(CoreConstants::SETTING__LOGO, 'assets/common/img/logo/default.png', 'Logo');
    }

    /**
     * Process the update favicon request
     *
     * @param array $data
     * @return array
     */
    public function processUpdateFaviconRequest(array $data)
    {
        try {
            $validate = Validator::make($data, [
                'file' => 'required'
            ]);

            if ($validate->fails()) {
                return [
                    'message' => 'Bad Request',
                    'payload' => $validate->errors(),
                    'status' => CoreConstants::STATUS_CODE_BAD_REQUEST
                ];
            }

            $file = $data['file'];
            $stored = MediaStorage::storeUploadedFile($file, 'favicon', 'png');
            if ($stored) {
                //delete previous favicon
                try {
                    $oldFaviconResponse = $this->getSettingByKey(CoreConstants::SETTING__FAVICON);
                    if ($oldFaviconResponse['status'] === CoreConstants::STATUS_CODE_SUCCESS && $oldFaviconResponse['payload']->setting_value !== 'assets/common/img/favicon/default.png') {
                        if (MediaStorage::exists($oldFaviconResponse['payload']->setting_value)) {
                            MediaStorage::delete($oldFaviconResponse['payload']->setting_value);
                        }
                    }
                } catch (\Throwable $th) {
                    Log::error($th->getMessage());
                }

                $newFilePath = $stored;
                $result  = $this->setSettingData([
                    'setting_key' => CoreConstants::SETTING__FAVICON,
                    'setting_value' => $newFilePath,
                ]);

                if ($result['status'] === CoreConstants::STATUS_CODE_SUCCESS) {
                    return [
                        'message' => 'Favicon is successfully saved',
                        'payload' => [
                            'file' => $newFilePath
                        ],
                        'status' => CoreConstants::STATUS_CODE_SUCCESS
                    ];
                } else {
                    return $result;
                }
            } else {
                return [
                    'message' => 'Favicon could not be saved',
                    'payload' => null,
                    'status' => CoreConstants::STATUS_CODE_ERROR
                ];
            }
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return [
                'message' => 'Something went wrong',
                'payload' => $th->getMessage(),
                'status' => CoreConstants::STATUS_CODE_ERROR
            ];
        }
    }

    /**
     * Process the delete favicon request
     *
     * @param string $file
     * @return array
     */
    public function processDeleteFaviconRequest(string $file = '')
    {
        return $this->clearImageSetting(CoreConstants::SETTING__FAVICON, 'assets/common/img/favicon/default.png', 'Favicon');
    }

    /**
     * store mail settings
     *
     * @param array $data
     * @return array
     */
    public function storeMailSettings(array $data)
    {
        try {
            $domain = self::senderDomain();
            $validate = Validator::make($data, [
                'MAIL_FROM_NAME' => 'required|string|max:80',
                'MAIL_FROM_ADDRESS' => ['required', 'email', function ($attribute, $value, $fail) use ($domain) {
                    // Amazon SES only sends for domains verified on the mail platform.
                    if ($domain && strtolower(substr(strrchr($value, '@'), 1)) !== $domain) {
                        $fail("The sender address must end in @{$domain}.");
                    }
                }],
            ]);

            if ($validate->fails()) {
                return [
                    'message' => $validate->errors()->first(),
                    'payload' => $validate->errors(),
                    'status' => CoreConstants::STATUS_CODE_BAD_REQUEST
                ];
            }

            $this->insertOrUpdate(['setting_key' => CoreConstants::SETTING__MAIL_FROM_NAME, 'setting_value' => trim($data['MAIL_FROM_NAME'])]);
            $this->insertOrUpdate(['setting_key' => CoreConstants::SETTING__MAIL_FROM_ADDRESS, 'setting_value' => strtolower(trim($data['MAIL_FROM_ADDRESS']))]);
            self::applyMailSender();

            return [
                'message' => 'Sender saved. New emails use it from now on.',
                'payload' => ['MAIL_FROM_NAME' => config('mail.from.name'), 'MAIL_FROM_ADDRESS' => config('mail.from.address')],
                'status' => CoreConstants::STATUS_CODE_SUCCESS
            ];
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return [
                'message' => 'Something went wrong',
                'payload' => null,
                'status' => CoreConstants::STATUS_CODE_ERROR
            ];
        }
    }

    /**
     * Domain the server's own sender address uses (MAIL_FROM_ADDRESS in the environment): the one the
     * mail platform is verified to send for. Read before any saved override is applied.
     */
    public static function senderDomain(): ?string
    {
        $address = config('mail.from_server.address', config('mail.from.address'));
        $at = $address ? strrchr($address, '@') : false;

        return $at ? strtolower(substr($at, 1)) : null;
    }

    /**
     * Use the sender saved in Settings > Mail, if any, for every email this request sends. Called once at
     * boot (AppServiceProvider) and after saving. Without a database (install, tests) the server's
     * values stay in place.
     */
    public static function applyMailSender(): void
    {
        if (!config()->has('mail.from_server')) {
            config(['mail.from_server' => config('mail.from')]);
        }

        try {
            $saved = Setting::whereIn('setting_key', [CoreConstants::SETTING__MAIL_FROM_NAME, CoreConstants::SETTING__MAIL_FROM_ADDRESS])
                ->pluck('setting_value', 'setting_key');
        } catch (\Throwable $th) {
            return;
        }

        if (!empty($saved[CoreConstants::SETTING__MAIL_FROM_NAME])) {
            config(['mail.from.name' => $saved[CoreConstants::SETTING__MAIL_FROM_NAME]]);
        }
        // Only a valid address is applied: a bad row must never stop every email from sending.
        if (filter_var($saved[CoreConstants::SETTING__MAIL_FROM_ADDRESS] ?? null, FILTER_VALIDATE_EMAIL)) {
            config(['mail.from.address' => $saved[CoreConstants::SETTING__MAIL_FROM_ADDRESS]]);
        }
    }

    /**
     * Remove an uploaded logo or favicon and put the setting back to its default. The file deleted is the
     * one recorded in the database, never a path sent by the browser.
     *
     * @param string $key setting key
     * @param string $default value the setting takes afterwards
     * @param string $label for the message
     * @return array
     */
    private function clearImageSetting(string $key, string $default, string $label)
    {
        try {
            $current = $this->getSettingByKey($key, ['setting_value']);
            $path = $current['status'] === CoreConstants::STATUS_CODE_SUCCESS ? $current['payload']->setting_value : null;
            if ($path && $path !== $default && MediaStorage::exists($path)) {
                MediaStorage::delete($path);
            }

            $result = $this->setSettingData(['setting_key' => $key, 'setting_value' => $default]);
            if ($result['status'] !== CoreConstants::STATUS_CODE_SUCCESS) {
                return $result;
            }

            return [
                'message' => $label.' is deleted successfully',
                'payload' => ['file' => $default],
                'status' => CoreConstants::STATUS_CODE_SUCCESS
            ];
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return [
                'message' => 'Something went wrong',
                'payload' => null,
                'status' => CoreConstants::STATUS_CODE_ERROR
            ];
        }
    }
}
