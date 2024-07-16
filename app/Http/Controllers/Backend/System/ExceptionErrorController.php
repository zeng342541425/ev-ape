<?php

namespace App\Http\Controllers\Backend\System;

use App\Constants\Constant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\System\ExceptionError\AmendedRequest;
use App\Http\Requests\Backend\System\ExceptionError\ListRequest;
use App\Models\Backend\System\ExceptionError;
use Symfony\Component\HttpFoundation\Response;

class ExceptionErrorController extends Controller
{
    /**
     * 獲取列表
     *
     * @param ListRequest $request
     * @return Response
     */
    public function list(ListRequest $request): Response
    {
        $param = $request->validated();

        $query = ExceptionError::query();

        if (!empty($param['is_solve'])) {
            $query->where('is_solve', $param['is_solve']);
        }

        if (isset($param['uid']) && !empty($param['uid'])) {
            $query->like('uid', $param['uid']);
        }
        if (isset($param['message']) && !empty($param['message'])) {
            $query->like('message', $param['message']);
        }

        if (!empty($param['created_at'])) {
            $query->timeBetween('created_at', $param['created_at']);
        }

        if (!empty($param['sort']) && !empty($param['order'])) {
            $query->orderBy($param['sort'], order_direction($param['order']));
        } else {
            $query->orderByDesc('created_at');
        }

        $list = $query->paginate($param['limit']);

        return $this->success([
            'list' => $list->items(),
            'total' => $list->total()
        ]);
    }

    /**
     * 修復信息
     *
     * @param AmendedRequest $request
     * @return Response
     */
    public function amended(AmendedRequest $request): Response
    {
        $admin = $request->user('admin');
        $id = $request->post('id');
        $item = ExceptionError::query()->find($id);
        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if($item->is_solve === Constant::COMMON_IS_YES){
            return $this->error(__('message.common.processed'));
        }

        $item->update([
            'is_solve' => Constant::COMMON_IS_YES
        ]);

        activity()
            ->useLog('exception')
            ->performedOn($item)
            ->causedBy($admin)
            ->log('The :subject.uid exception amended by :causer.name');

        return $this->success(msg: __('message.common.update.success'));
    }


}
