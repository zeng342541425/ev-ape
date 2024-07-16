<?php

namespace App\Http\Controllers\Backend\System;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\System\DictType\CreateRequest;
use App\Http\Requests\Backend\System\DictType\ListRequest;
use App\Http\Requests\Backend\System\DictType\UpdateRequest;
use App\Http\Requests\Common\IdRequest;
use App\Models\Backend\System\DictData;
use App\Models\Backend\System\DictType;
use Symfony\Component\HttpFoundation\Response;

class DictTypeController extends Controller
{
    /**
     * 列表
     *
     * @param ListRequest $request
     * @return Response
     */
    public function list(ListRequest $request): Response
    {
        $param = $request->all();
        $query = DictType::query();

        if (isset($param['name']) && $param['name'] != '') {
            $query->like('name', $param['name']);
        }
        if (!empty($param['type'])) {
            $query->where('type', $param['type']);
        }
        if (!empty($param['status'])) {
            $query->where('status', $param['status']);
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
            'total' => $list->total(),
        ]);
    }

    /**
     * 下拉
     *
     * @return Response
     */
    public function all(): Response
    {
        $list = DictType::query()->select([
            'id', 'name', 'type', 'status'
        ])->get();

        return $this->success([
            'list' => $list
        ]);
    }

    /**
     * 詳情
     *
     * @param IdRequest $request
     * @return Response
     */
    public function detail(IdRequest $request): Response
    {
        $id = $request->post('id');

        $item = DictType::query()->find($id);
        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        return $this->success([
            'item' => $item
        ]);
    }

    /**
     * 創建
     *
     * @param CreateRequest $request
     * @return Response
     */
    public function create(CreateRequest $request): Response
    {
        $param = $request->only([
            'name', 'type', 'remark', 'status'
        ]);

        if (DictType::type($param['type'])->first()) {
            return $this->error(__('message.dict_type.type_exist'));
        }


        $item = DictType::query()->create($param);

        DictData::forgetRedis();
        return $this->success([
            'item' => $item
        ], __('message.common.create.success'));
    }

    /**
     * 更新
     *
     * @param UpdateRequest $request
     * @return Response
     */
    public function update(UpdateRequest $request): Response
    {
        $id = $request->post('id');
        $param = $request->all([
            'name', 'type', 'status', 'remark'
        ]);

        $param['remark'] = $param['remark'] ?? '';

        $item = DictType::query()->find($id);
        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if ($param['type'] != $item->type && DictType::type($param['type'])->first()) {
            return $this->error(__('message.dict_type.type_exist'));
        }


        $item->update($param);

        DictData::forgetRedis();
        return $this->success([
            'item' => $item
        ], __('message.common.update.success'));
    }

    /**
     * 刪除
     *
     * @param IdRequest $request
     * @return Response
     */
    public function delete(IdRequest $request): Response
    {
        $id = $request->post('id');
        $item = DictType::query()->find($id);
        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }
        $item->delete();
        DictData::query()->where('dict_type_id', $id)->delete();

        DictData::forgetRedis();
        return $this->success(msg: __('message.common.delete.success'));
    }
}
