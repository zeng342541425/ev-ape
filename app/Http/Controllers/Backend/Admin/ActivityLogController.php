<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Admin\ActivityLog\ListRequest;
use App\Models\Backend\Activity;
use App\Models\Backend\Admin\ActivityLog;
use Symfony\Component\HttpFoundation\Response;

class ActivityLogController extends Controller
{
    /**
     * 獲取列表
     *
     * @param ListRequest $request
     * @return Response
     */
    public function list(ListRequest $request): Response
    {
        $param = $request->all();

        $query = ActivityLog::query();

        if (isset($param['log_name']) && $param['log_name'] != '') {
            $query->like('log_name', $param['log_name']);
        }
        if (isset($param['description']) && $param['description'] != '') {
            $query->like('description', $param['description']);
        }
        if (!empty($param['subject_id'])) {
            $query->where('subject_id', $param['subject_id']);
        }
        if (!empty($param['subject_type'])) {
            $query->where('subject_type', $param['subject_type']);
        }
        if (!empty($param['causer_id'])) {
            $query->where('causer_id', $param['causer_id']);
        }
        if (!empty($param['causer_type'])) {
            $query->where('causer_type', $param['causer_type']);
        }
        if (isset($param['properties']) && $param['properties'] != '') {
            $query->like('properties', $param['properties']);
        }
        if (!empty($param['created_at'])) {
            $query->timeBetween('created_at', $param['created_at']);
        }

        $list = $query->paginate($param['limit']);

        return $this->success([
            'list' => $list->items(),
            'total' => $list->total(),
        ]);
    }

    /**
     * 查詢類型
     * @return \Illuminate\Http\JsonResponse
     */
    public function queryType()
    {
        $logNames = ActivityLog::query()->whereNotNull('log_name')
            ->groupBy('log_name')->pluck('log_name')->toArray();
        $subjectType = ActivityLog::query()->whereNotNull('subject_type')
            ->groupBy('subject_type')
            ->pluck('subject_type')->toArray();
        $causerType = ActivityLog::query()->whereNotNull('causer_type')
            ->groupBy('causer_type')->pluck('causer_type')->toArray();

        return $this->success([
            'log_name' => $logNames,
            'subject_type' => $subjectType,
            'causer_type' => $causerType,
        ]);
    }

}
