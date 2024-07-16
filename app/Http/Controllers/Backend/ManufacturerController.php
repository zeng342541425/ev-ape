<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Manufacturer\CreateRequest;
use App\Http\Requests\Backend\Manufacturer\ListRequest;
use App\Http\Requests\Backend\Manufacturer\IdRequest;
use App\Http\Requests\Backend\Manufacturer\UpdateRequest;
use App\Models\Common\Manufacturer;
use App\Models\Parking\ChargingPile;
use Symfony\Component\HttpFoundation\Response;

class ManufacturerController extends Controller
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

        $query = Manufacturer::query();


        $query->orderByDesc('created_at');

        $list = $query->paginate($param['limit']);

        return $this->success([
            'list' => $list->items(),
            'total' => $list->total()
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

        $item = Manufacturer::query()->find($id);

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
            'name',
        ]);

        if (Manufacturer::query()->where('name', $param['name'])->exists()) {
            return $this->error('廠商已存在');
        }

        $item = Manufacturer::query()->create($param);

        return $this->success();
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
            'name',
        ]);

        $item = Manufacturer::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (Manufacturer::query()->where('name', $param['name'])->whereNot('id', $id)->exists()) {
            return $this->error('廠商已存在');
        }

        if (!$item->update($param)) {
            return $this->error(__('message.common.update.fail'));
        }

        return $this->success();
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

        $item = Manufacturer::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (ChargingPile::query()->where('manufacturer_id', $id)->exists()) {
            return $this->error('充電樁綁定廠商，無法刪除');
        }

        if (!$item->delete()) {
            return $this->error(__('message.common.delete.fail'));
        }

        return $this->success(msg: __('message.common.delete.success'));
    }
}
