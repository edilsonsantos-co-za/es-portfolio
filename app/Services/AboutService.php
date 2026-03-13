<?php

namespace App\Services;

use CoreConstants;
use App\Models\About;
use App\Services\Contracts\AboutInterface;
use Illuminate\Support\Facades\Storage;
use Log;
use Str;
use Validator;

class AboutService implements AboutInterface
{
    /**
     * Eloquent instance
     *
     * @var About
     */
    private $model;

    /**
     * Create a new service instance
     *
     * @param About $about
     * @return void
     */
    public function __construct(About $about)
    {
        $this->model = $about;
    }

    /**
     * Get all about fields
     *
     * @param array $select
     * @return array
     */
    public function getAll(array $select = ['*'])
    {
        try {
            $result = $this->model->select($select)->first();

            if ($result) {
                return [
                    'message' => 'Data is fetched successfully',
                    'payload' => $result,
                    'status' => CoreConstants::STATUS_CODE_SUCCESS
                ];
            } else {
                return [
                    'message' => 'No result found',
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
     * Store/update data
     *
     * @param array $data
     * @return array
     */
    public function store(array $data)
    {
        try {
            $validate = Validator::make($data, [
                'name' => 'required|string',
                'email' => 'required|email'
            ]);

            if ($validate->fails()) {
                return [
                    'message' => 'Bad Request',
                    'payload' => $validate->errors(),
                    'status' => CoreConstants::STATUS_CODE_BAD_REQUEST
                ];
            }

            $newData['name'] = $data['name'];
            $newData['email'] = $data['email'];
            $newData['phone'] = isset($data['phone']) ? $data['phone'] : null;
            $newData['address'] = isset($data['address']) ? $data['address'] : null;
            $newData['description'] = isset($data['description']) ? $data['description'] : null;
            
            // Handle avatar and cover from seeder data
            if (isset($data['avatar'])) {
                $newData['avatar'] = $data['avatar'];
            }
            if (isset($data['cover'])) {
                $newData['cover'] = $data['cover'];
            }

            if (isset($data['seederCV'])) {
                $newData['cv'] = $data['seederCV'];
            }
            
            $newTagLinesArray = [];
            if (isset($data['taglines'])) {
                foreach ($data['taglines'] as $key => $tagline) {
                    if ($tagline !== null && $tagline !== '') {
                        array_push($newTagLinesArray, $tagline);
                    }
                }
            }
            $newData['taglines'] = count($newTagLinesArray) ? json_encode($newTagLinesArray) : null;
            
            $newSocialLinksArray = [];
            if (isset($data['social_links'])) {
                foreach ($data['social_links'] as $key => $socialLink) {
                    if ($socialLink !== '' && !empty($socialLink['title']) && !empty($socialLink['link']) && !empty($socialLink['iconClass'])) {
                        array_push($newSocialLinksArray, $socialLink);
                    }
                }
            }
            $newData['social_links'] = count($newSocialLinksArray) ? json_encode($newSocialLinksArray) : null;
            
            $existedRecord = $this->getAll();

            if ($existedRecord['status'] === CoreConstants::STATUS_CODE_SUCCESS) {
                $existedRecord = $existedRecord['payload'];
                $result = $existedRecord->update($newData);
            } else {
                // Only set defaults if not already provided (e.g., from seeder)
                if (!isset($newData['avatar'])) {
                    $newData['avatar'] = 'assets/common/img/avatar/default.png';
                }
                if (!isset($newData['cover'])) {
                    $newData['cover'] = 'assets/common/img/cover/default.png';
                }
                $result = $this->model->create($newData);
            }

            if ($result) {
                return [
                    'message' => 'Data is successfully updated',
                    'payload' => $result,
                    'status' => CoreConstants::STATUS_CODE_SUCCESS
                ];
            } else {
                return [
                    'message' => 'Something went wrong',
                    'payload' => null,
                    'status'  => CoreConstants::STATUS_CODE_ERROR
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
     * Process the update avatar request
     *
     * @param array $data
     * @return array
     */
    public function processUpdateAvatarRequest(array $data)
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
            $extension = $file->extension() ? $file->extension() : 'png';
            $fileName = Str::random(10). '_'. time() .'.'. $extension;

            $stored = Storage::disk('public')->putFileAs('avatar', $file, $fileName);
            if ($stored) {
                //delete previous avatar
                $oldAvatarResponse = $this->getAll(['avatar', 'id']);
                try {
                    if ($oldAvatarResponse['status'] === CoreConstants::STATUS_CODE_SUCCESS) {
                        $oldAvatar = $oldAvatarResponse['payload']->avatar;
                        if ($oldAvatar !== 'assets/common/img/avatar/default.png') {
                            $oldPath = preg_replace('/^storage\//', '', $oldAvatar);
                            if (Storage::disk('public')->exists($oldPath)) {
                                Storage::disk('public')->delete($oldPath);
                            }
                        }
                    }
                } catch (\Throwable $th) {
                    Log::error($th->getMessage());
                }

                $result = $oldAvatarResponse['payload'];
                $newFilePath = 'storage/' . $stored;

                $updateResponse = $result->update([
                    'avatar' => $newFilePath
                ]);

                if ($updateResponse) {
                    return [
                        'message' => 'Avatar is successfully saved',
                        'payload' => [
                            'file' => $newFilePath
                        ],
                        'status' => CoreConstants::STATUS_CODE_SUCCESS
                    ];
                } else {
                    return [
                        'message' => 'Something went wrong',
                        'payload' => null,
                        'status' => CoreConstants::STATUS_CODE_ERROR
                    ];
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
     * Process the delete avatar request
     *
     * @param string $file
     * @return array
     */
    public function processDeleteAvatarRequest(string $file)
    {
        try {
            $storagePath = preg_replace('/^storage\//', '', $file);
            if (!Storage::disk('public')->exists($storagePath)) {
                return [
                    'message' => 'The file can\'t be found',
                    'payload' => $file,
                    'status' => CoreConstants::STATUS_CODE_NOT_FOUND
                ];
            }

            if (Storage::disk('public')->delete($storagePath)) {
                $defaultAvatar = 'assets/common/img/avatar/default.png';
                $result = $this->getAll();

                if ($result['status'] !== CoreConstants::STATUS_CODE_SUCCESS) {
                    return $result;
                } else {
                    $result = $result['payload'];
                }

                $updateResponse = $result->update([
                    'avatar' => $defaultAvatar
                ]);

                if ($updateResponse) {
                    return [
                        'message' => 'File is deleted successfully',
                        'payload' => [
                            'file' => $defaultAvatar
                        ],
                        'status' => CoreConstants::STATUS_CODE_SUCCESS
                    ];
                } else {
                    return [
                        'message' => 'Something went wrong',
                        'payload' => null,
                        'status' => CoreConstants::STATUS_CODE_ERROR
                    ];
                }
            } else {
                return [
                    'message' => 'File could not be deleted',
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
     * Process the update cover request
     *
     * @param array $data
     * @return array
     */
    public function processUpdateCoverRequest(array $data)
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
            $extension = $file->extension() ? $file->extension() : 'png';
            $fileName = Str::random(10). '_'. time() .'.'. $extension;

            $stored = Storage::disk('public')->putFileAs('cover', $file, $fileName);
            if ($stored) {
                //delete previous cover
                $oldCoverResponse = $this->getAll(['cover', 'id']);
                try {
                    if ($oldCoverResponse['status'] === CoreConstants::STATUS_CODE_SUCCESS) {
                        $oldCover = $oldCoverResponse['payload']->cover;
                        if ($oldCover !== 'assets/common/img/cover/default.png') {
                            $oldPath = preg_replace('/^storage\//', '', $oldCover);
                            if (Storage::disk('public')->exists($oldPath)) {
                                Storage::disk('public')->delete($oldPath);
                            }
                        }
                    }
                } catch (\Throwable $th) {
                    Log::error($th->getMessage());
                }

                $result = $oldCoverResponse['payload'];
                $newFilePath = 'storage/' . $stored;

                $updateResponse = $result->update([
                    'cover' => $newFilePath
                ]);

                if ($updateResponse) {
                    return [
                        'message' => 'cover is successfully saved',
                        'payload' => [
                            'file' => $newFilePath
                        ],
                        'status' => CoreConstants::STATUS_CODE_SUCCESS
                    ];
                } else {
                    return [
                        'message' => 'Something went wrong',
                        'payload' => null,
                        'status' => CoreConstants::STATUS_CODE_ERROR
                    ];
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
     * Process the delete cover request
     *
     * @param string $file
     * @return array
     */
    public function processDeleteCoverRequest(string $file)
    {
        try {
            $storagePath = preg_replace('/^storage\//', '', $file);
            if (!Storage::disk('public')->exists($storagePath)) {
                return [
                    'message' => 'The file can\'t be found',
                    'payload' => $file,
                    'status' => CoreConstants::STATUS_CODE_NOT_FOUND
                ];
            }

            if (Storage::disk('public')->delete($storagePath)) {
                $defaultCover = 'assets/common/img/cover/default.png';
                $result = $this->getAll();

                if ($result['status'] !== CoreConstants::STATUS_CODE_SUCCESS) {
                    return $result;
                } else {
                    $result = $result['payload'];
                }

                $updateResponse = $result->update([
                    'cover' => $defaultCover
                ]);

                if ($updateResponse) {
                    return [
                        'message' => 'File is deleted successfully',
                        'payload' => [
                            'file' => $defaultCover
                        ],
                        'status' => CoreConstants::STATUS_CODE_SUCCESS
                    ];
                } else {
                    return [
                        'message' => 'Something went wrong',
                        'payload' => null,
                        'status' => CoreConstants::STATUS_CODE_ERROR
                    ];
                }
            } else {
                return [
                    'message' => 'File could not be deleted',
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
     * Process the update CV request
     *
     * @param array $data
     * @return array
     */
    public function processUpdateCVRequest(array $data)
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
            $extension = $file->extension() ? $file->extension() : 'pdf';
            $fileName = Str::random(10). '_'. time() .'.'. $extension;

            $stored = Storage::disk('public')->putFileAs('cv', $file, $fileName);
            if ($stored) {
                //delete previous cv
                $oldCVResponse = $this->getAll(['cv', 'id']);
                try {
                    if ($oldCVResponse['status'] === CoreConstants::STATUS_CODE_SUCCESS && $oldCVResponse['payload']->cv !== null) {
                        $oldPath = preg_replace('/^storage\//', '', $oldCVResponse['payload']->cv);
                        if (Storage::disk('public')->exists($oldPath)) {
                            Storage::disk('public')->delete($oldPath);
                        }
                    }
                } catch (\Throwable $th) {
                    Log::error($th->getMessage());
                }

                $result = $oldCVResponse['payload'];
                $newFilePath = 'storage/' . $stored;

                $updateResponse = $result->update([
                    'cv' => $newFilePath
                ]);
                
                if ($updateResponse) {
                    return [
                        'message' => 'CV is successfully saved',
                        'payload' => [
                            'file' => $newFilePath
                        ],
                        'status' => CoreConstants::STATUS_CODE_SUCCESS
                    ];
                } else {
                    return [
                        'message' => 'Something went wrong',
                        'payload' => null,
                        'status' => CoreConstants::STATUS_CODE_ERROR
                    ];
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
     * Process the delete CV request
     *
     * @param string $file
     * @return array
     */
    public function processDeleteCVRequest(string $file)
    {
        try {
            $storagePath = preg_replace('/^storage\//', '', $file);
            if (!Storage::disk('public')->exists($storagePath)) {
                return [
                    'message' => 'The file can\'t be found',
                    'payload' => $file,
                    'status' => CoreConstants::STATUS_CODE_NOT_FOUND
                ];
            }

            if (Storage::disk('public')->delete($storagePath)) {
                $result = $this->getAll();
                if ($result['status'] !== CoreConstants::STATUS_CODE_SUCCESS) {
                    return $result;
                } else {
                    $result = $result['payload'];
                }

                $updateResponse = $result->update([
                    'cv' => null
                ]);

                if ($updateResponse) {
                    return [
                        'message' => 'File is deleted successfully',
                        'payload' => [
                            'file' => null
                        ],
                        'status' => CoreConstants::STATUS_CODE_SUCCESS
                    ];
                } else {
                    return [
                        'message'  => 'Something went wrong',
                        'payload' => null,
                        'status'  => CoreConstants::STATUS_CODE_ERROR
                    ];
                }
            } else {
                return [
                    'message'  => 'File could not be deleted',
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
}
