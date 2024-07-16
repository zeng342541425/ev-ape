<?php

namespace App\Http\Controllers\Backend\System;

use App\Constants\Constant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\System\DictData\CreateRequest;
use App\Http\Requests\Backend\System\DictData\ListRequest;
use App\Http\Requests\Backend\System\DictData\UpdateRequest;
use App\Http\Requests\Common\IdRequest;
use App\Models\Backend\System\DictData;
use App\Models\Backend\System\DictType;
use Symfony\Component\HttpFoundation\Response;

class DictDataController extends Controller
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

        $query = DictData::dictTypeId($param['dict_type_id']);

        if (isset($param['label']) && $param['label'] != '') {
            $query->like('label', $param['label']);
        }
        if (isset($param['value']) && $param['value'] != '') {
            $query->like('value', $param['value']);
        }
        if (!empty($param['default'])) {
            $query->where('default', $param['default']);
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
     * 所有列表
     * @return Response
     */
    public function all(): Response
    {
        return $this->success([
            'list' => DictData::selectAll()
        ], __('message.common.search.success'));
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

        $item = DictData::query()->find($id);
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
            'dict_type_id', 'label', 'value', 'sort', 'list_class', 'default', 'status', 'remark'
        ]);
        $param['list_class'] = $param['list_class'] ?? '';
        $param['remark'] = $param['remark'] ?? '';
        $param['sort'] = $param['sort'] ?? 0;

        if (!DictType::query()->find($param['dict_type_id'])) {
            return $this->error(__('message.dict_type.type_not_found'));
        }

        $item = DictData::query()->create($param);
        if ($param['default'] == Constant::COMMON_IS_YES) {
            DictData::setDefault($item);
        }

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
        $param = $request->only([
            'label', 'value', 'sort', 'list_class', 'default', 'status', 'remark'
        ]);
        $param['list_class'] = $param['list_class'] ?? '';
        $param['remark'] = $param['remark'] ?? '';
        $param['sort'] = $param['sort'] ?? 0;

        $item = DictData::query()->find($id);
        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if ($param['default'] != $item['default'] && $param['default'] == Constant::COMMON_IS_YES) {
            DictData::setDefault($item);
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

        $item = DictData::query()->find($id);
        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }
        $item->delete();

        DictData::forgetRedis();
        return $this->success(msg: __('message.common.delete.success'));
    }

    /**
     * 樣式列表
     * @return Response
     */
    public function listClass(): Response
    {
        return $this->success([
            'list' => [
                ['name' => __('message.dict_data.list_class_type.default'), 'value' => ''],
                ['name' => __('message.dict_data.list_class_type.primary'), 'value' => 'primary'],
                ['name' => __('message.dict_data.list_class_type.success'), 'value' => 'success'],
                ['name' => __('message.dict_data.list_class_type.info'), 'value' => 'info'],
                ['name' => __('message.dict_data.list_class_type.warning'), 'value' => 'warning'],
                ['name' => __('message.dict_data.list_class_type.danger'), 'value' => 'danger'],
            ]
        ], __('message.common.search.success'));
    }
}
