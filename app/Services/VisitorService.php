<?php

namespace App\Services;

use CoreConstants;
use App\Models\Visitor;
use App\Services\Contracts\VisitorInterface;
use App\Helpers\Utils;
use Carbon\Carbon;
use DB;
use Cache;
use Log;
use Validator;
use Jenssegers\Agent\Agent;
use Stevebauman\Location\Facades\Location;

class VisitorService implements VisitorInterface
{
    /**
     * Eloquent instance
     *
     * @var Visitor
     */
    private $model;

    /**
     * Create a new service instance
     *
     * @param Visitor $visitor
     * @return void
     */
    public function __construct(Visitor $visitor)
    {
        $this->model = $visitor;
    }

    /**
     * Get all fields
     *
     * @param array $select
     * @return array
     */
    public function getAll(array $select = ['*'])
    {
        try {
            $result = $this->model->select($select)->get();
            
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
                'tracking_id' => 'required',
            ]);

            if ($validate->fails()) {
                return [
                    'message' => 'Validation Error',
                    'payload' => $validate->errors(),
                    'status' => CoreConstants::STATUS_CODE_BAD_REQUEST
                ];
            }

            $agent = new Agent();

            $trackingId = $data['tracking_id'];
            $isNew = true;
            $existingData = $this->getByTrackingId($trackingId);

            if ($existingData['status'] === CoreConstants::STATUS_CODE_SUCCESS) {
                $isNew = false;
            }

            $ip = request()->getClientIp();
            $isDesktop = $agent->isDesktop();
            $browser = $agent->browser();
            $platform = $agent->platform();
            $position = $this->lookupLocation($ip);

            $newData['tracking_id'] = $trackingId;
            $newData['is_new'] = $isNew;
            $newData['ip'] = $ip;
            $newData['is_desktop'] = $isDesktop;
            $newData['browser'] = $browser;
            $newData['platform'] = $platform;
            $newData['location'] = $position['country'] ?? 'Unknown';
            $newData['city'] = $position['city'] ?? null;
            $newData['region'] = $position['region'] ?? null;
            $newData['user_agent'] = mb_substr((string) request()->userAgent(), 0, 1000) ?: null;
            
            if (isset($data['id'])) {
                $result = $this->getById($data['id'], ['id']);
                if ($result['status'] !== CoreConstants::STATUS_CODE_SUCCESS) {
                    return $result;
                } else {
                    $existingData = $result['payload'];
                }
                $result = $existingData->update($newData);
            } else {
                $result = $this->model->create($newData);
            }

            if ($result) {
                return [
                    'message' => isset($data['id']) ? 'Data is successfully updated' : 'Data is successfully saved',
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
     * Resolve an IP to country/region/city, cached for a day so repeat visits skip the API.
     * Private and reserved addresses are never sent out.
     *
     * @return array{country?: string, region?: string|null, city?: string|null}
     */
    private function lookupLocation(?string $ip): array
    {
        if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return [];
        }

        return Cache::remember('visitor-location:' . $ip, now()->addDay(), function () use ($ip) {
            $position = Location::get($ip);

            if (!$position || empty($position->countryName)) {
                return [];
            }

            return [
                'country' => $position->countryName,
                'region' => $position->regionName ?: null,
                'city' => $position->cityName ?: null,
            ];
        });
    }

    /**
     * Visits grouped by IP address: count, first/last seen and the details of the latest visit.
     *
     * @param array $data page, pageSize, keyword, startDate, endDate
     * @return array
     */
    public function getIpAddresses(array $data)
    {
        try {
            $perPage = min(max((int) ($data['pageSize'] ?? 20), 5), 100);
            $query = $this->model->newQuery();

            if (!empty($data['startDate'])) {
                $query->where('created_at', '>=', Carbon::parse($data['startDate'])->format('Y-m-d H:i:s'));
            }
            if (!empty($data['endDate'])) {
                $query->where('created_at', '<=', Carbon::parse($data['endDate'])->format('Y-m-d H:i:s'));
            }
            if (!empty($data['keyword'])) {
                $keyword = '%' . str_replace(['%', '_'], ['\\%', '\\_'], $data['keyword']) . '%';
                $query->where(function ($q) use ($keyword) {
                    foreach (['ip', 'location', 'city', 'region', 'browser', 'platform', 'user_agent'] as $column) {
                        $q->orWhere($column, 'like', $keyword);
                    }
                });
            }

            $page = $query
                ->select('ip', DB::raw('count(*) as visits'), DB::raw('min(created_at) as first_seen'), DB::raw('max(created_at) as last_seen'), DB::raw('max(id) as last_id'))
                ->groupBy('ip')
                ->orderByDesc('last_seen')
                ->paginate($perPage);

            $latest = $this->model->whereIn('id', collect($page->items())->pluck('last_id'))->get()->keyBy('id');

            $page->getCollection()->transform(function ($row) use ($latest) {
                $visit = $latest->get($row->last_id);

                return [
                    'ip' => $row->ip,
                    'visits' => (int) $row->visits,
                    'first_seen' => Carbon::parse($row->first_seen)->toIso8601String(),
                    'last_seen' => Carbon::parse($row->last_seen)->toIso8601String(),
                    'country' => $visit->location ?? null,
                    'region' => $visit->region ?? null,
                    'city' => $visit->city ?? null,
                    'browser' => $visit->browser ?? null,
                    'platform' => $visit->platform ?? null,
                    'is_desktop' => $visit ? (bool) $visit->is_desktop : null,
                    'user_agent' => $visit->user_agent ?? null,
                ];
            });

            return [
                'message' => 'Data is fetched successfully',
                'payload' => $page,
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
     * Store item for seed
     *
     * @param array $request
     * @return array
     */
    public function forceStore(array $data)
    {
        try {
            $validate = Validator::make($data, [
                'tracking_id' => 'required',
                'is_new' => 'required',
                'ip' => 'required',
                'is_desktop' => 'required',
                'browser' => 'required',
                'platform' => 'required',
                'location' => 'required',
            ]);

            if ($validate->fails()) {
                return [
                    'message' => 'Validation Error',
                    'payload' => $validate->errors(),
                    'status'  => CoreConstants::STATUS_CODE_BAD_REQUEST
                ];
            }

            $newData['tracking_id'] = $data['tracking_id'];
            $newData['is_new'] = $data['is_new'];
            $newData['ip'] = $data['ip'];
            $newData['is_desktop'] = $data['is_desktop'];
            $newData['browser'] = $data['browser'];
            $newData['platform'] = $data['platform'];
            $newData['location'] = $data['location'];
            isset($data['created_at']) && $newData['created_at'] = $data['created_at'];

            $response = $this->model->create($newData);

            if ($response) {
                return [
                    'message' => 'Data is successfully saved',
                    'payload' => $response,
                    'status'  => CoreConstants::STATUS_CODE_SUCCESS
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
                'status'  => CoreConstants::STATUS_CODE_ERROR
            ];
        }
    }

    /**
     * Fetch item by tracking id
     *
     * @param string $trackingId
     * @param array $select
     * @return array
     */
    public function getByTrackingId(string $trackingId, array $select = ['*'])
    {
        try {
            $data = $this->model->select($select)->where('tracking_id', $trackingId)->first();
            
            if ($data) {
                return [
                    'message' => 'Data is fetched successfully',
                    'payload' => $data,
                    'status'  => CoreConstants::STATUS_CODE_SUCCESS
                ];
            } else {
                return [
                    'message' => 'No result is found',
                    'payload' => null,
                    'status'  => CoreConstants::STATUS_CODE_NOT_FOUND
                ];
            }
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return [
                'message' => 'Something went wrong',
                'payload' => $th->getMessage(),
                'status'  => CoreConstants::STATUS_CODE_ERROR
            ];
        }
    }

    /**
     * Fetch item by id
     *
     * @param int $id
     * @param array $select
     * @return array
     */
    public function getById(int $id, array $select = ['*'])
    {
        try {
            $data = $this->model->select($select)->where('id', $id)->first();
            
            if ($data) {
                return [
                    'message' => 'Data is fetched successfully',
                    'payload' => $data,
                    'status' => CoreConstants::STATUS_CODE_SUCCESS
                ];
            } else {
                return [
                    'message' => 'No result is found',
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
     * Get all fields with paginate
     *
     * @param array $data
     * @param array $select
     * @return array
     */
    public function getAllWithPaginate(array $data, array $select = ['*'])
    {
        try {
            $params = Utils::safeJsonDecode($data['params'] ?? null);
            $perPage = !empty($params) && !empty($params->pageSize) ? $params->pageSize : 10;
            
            if (!empty($data['sorter']) && count(Utils::safeJsonDecode($data['sorter'], true))) {
                $sorter = Utils::safeJsonDecode($data['sorter'], true);
                foreach ($sorter as $key => $value) {
                    $sortBy = $key;
                    $sortType = ($value === 'ascend' ? 'asc' : 'desc');
                }
            } else {
                $sortBy = 'created_at';
                $sortType = 'desc';
            }
            
            $result = $this->model->select($select)->orderBy($sortBy, $sortType);

            if (!empty($params) && !empty($params->keyword) && $params->keyword !== '') {
                $searchQuery = $params->keyword;
                $columns = !empty($data['columns']) ? $data['columns'] : null;
                
                if ($columns) {
                    $result->where(function ($query) use ($columns, $searchQuery) {
                        foreach ($columns as $key => $column) {
                            $columnData = Utils::safeJsonDecode($column);
                            if (!empty($columnData->search) && $columnData->search === true) {
                                $fieldName = $columnData->dataIndex;
                                $query->orWhere($fieldName, 'like', '%' . $searchQuery . '%');
                            }
                        }
                    });
                }
            }

            $result = $result->paginate($perPage);
            
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
     * Delete items by id array
     *
     * @param array $ids
     * @return array
     */
    public function deleteByIds(array $ids)
    {
        try {
            $data = $this->model->whereIn('id', $ids)->delete();
            
            if ($data) {
                return [
                    'message' => 'Data is deleted successfully',
                    'payload' => $data,
                    'status' => CoreConstants::STATUS_CODE_SUCCESS
                ];
            } else {
                return [
                    'message' => 'Nothing to Delete',
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
     * Get visitors stats
     *
     * @param string $startDate UTC start date
     * @param string $endEnd UTC end date
     * @return array
     */
    public function getVisitorsStats($startDate = null, $endEnd = null)
    {
        try {
            $result = $this->model;

            if ($startDate) {
                $startDate = Carbon::parse($startDate)->format('Y-m-d H:i:s');
                $result = $result->where('created_at', '>=', $startDate);
            }
            if ($endEnd) {
                $endEnd = Carbon::parse($endEnd)->format('Y-m-d H:i:s');
                $result = $result->where('created_at', '<=', $endEnd);
            }

            //visitors
            $data['visitors']['total'] = $totalVisitors = (clone $result)->count();
            $data['visitors']['new'] = (clone $result)->where('is_new', CoreConstants::TRUE)->count();
            $data['visitors']['old'] = (clone $result)->where('is_new', CoreConstants::FALSE)->count();
            
            //location
            $data['location'] = (clone $result)->select('location', DB::raw('count(*) as total'))->groupBy('location')->get();

            //device
            if ($totalVisitors) {
                $data['device']['desktop'] = ((clone $result)->where('is_desktop', CoreConstants::TRUE)->count());
                $data['device']['mobile'] = ((clone $result)->where('is_desktop', CoreConstants::FALSE)->count());

                // $data['device']['mobile'] = ((clone $result)->where('is_desktop', CoreConstants::FALSE)->count() * 100) / $totalVisitors;
            } else {
                $data['device']['desktop'] = 0;
                $data['device']['mobile'] = 0;
            }

            //browser
            $data['browser'] = (clone $result)->select('browser', DB::raw('count(*) as total'))->groupBy('browser')->get();

            //platform
            $data['platform'] = (clone $result)->select('platform', DB::raw('count(*) as total'))->groupBy('platform')->get();

            //daily: new vs returning per day, gaps filled with zeros
            $data['daily'] = $this->dailySeries(clone $result, $startDate, $endEnd);
            

            return [
                'message' => 'Data is fetched Successfully',
                'payload' => $data,
                'status'  => CoreConstants::STATUS_CODE_SUCCESS
            ];
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return [
                'message' => 'Something went wrong',
                'payload' => $th->getMessage(),
                'status'  => CoreConstants::STATUS_CODE_ERROR
            ];
        }
    }

    /**
     * Visits per day split into new and returning, one row per day in the range.
     * Without a range it covers the last 30 days.
     *
     * @return array<int, array{date: string, new: int, returning: int}>
     */
    private function dailySeries($query, $startDate = null, $endDate = null): array
    {
        $from = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->subDays(29)->startOfDay();
        $to = $endDate ? Carbon::parse($endDate)->startOfDay() : now()->startOfDay();

        if ($from->diffInDays($to) > 366) {
            $from = $to->copy()->subDays(366);
        }

        $rows = $query->where('created_at', '>=', $from)
            ->select(DB::raw('DATE(created_at) as day'), 'is_new', DB::raw('count(*) as total'))
            ->groupBy('day', 'is_new')
            ->get();

        $counts = [];
        foreach ($rows as $row) {
            $counts[$row->day][(int) $row->is_new === CoreConstants::TRUE ? 'new' : 'returning'] = (int) $row->total;
        }

        $series = [];
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $key = $day->toDateString();
            $series[] = [
                'date' => $key,
                'new' => $counts[$key]['new'] ?? 0,
                'returning' => $counts[$key]['returning'] ?? 0,
            ];
        }

        return $series;
    }

    /**
     * Delete all entries
     *
     * @return array
     */
    public function deleteAll()
    {
        try {
            $data = $this->model->truncate();
            
            if ($data) {
                return [
                    'message' => 'Stats are reset successfully',
                    'payload' => null,
                    'status'  => CoreConstants::STATUS_CODE_SUCCESS
                ];
            } else {
                return [
                    'message' => 'Nothing to remove',
                    'payload' => null,
                    'status'  => CoreConstants::STATUS_CODE_NOT_FOUND
                ];
            }
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return [
                'message' => 'Something went wrong',
                'payload' => $th->getMessage(),
                'status'  => CoreConstants::STATUS_CODE_ERROR
            ];
        }
    }
}
