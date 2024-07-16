<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\ChargingPowers\CreateRequest;
use App\Http\Requests\Backend\ChargingPowers\ListRequest;
use App\Http\Requests\Backend\ChargingPowers\IdRequest;
use App\Http\Requests\Backend\ChargingPowers\UpdateRequest;
use App\Models\Parking\ChargingPile;
use App\Models\Parking\ChargingPower;
use Symfony\Component\HttpFoundation\Response;

class ChargingPowersController extends Controller
{
    /**
     * 列表
     *
     * @return Response
     */
    public function list(): Response
    {

        $query = ChargingPower::query();


        $query->orderByDesc('created_at');

        $list = $query->get()->toArray();

        $res = [];
        foreach($list as $v) {
            if ($v['type'] == 1) {
                $res['power_list'][] = $v;
            } elseif ($v['type'] == 2) {
                $res['specification_list'][] = $v;
            }
        }

        return $this->success($res);
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

        $item = ChargingPower::query()->find($id);

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
            'value', 'type',
        ]);

        $item = ChargingPower::query()->create($param);

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
            'value',
        ]);

        $item = ChargingPower::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
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

        $item = ChargingPower::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (ChargingPile::query()->where('power_id', $id)->exists()) {
            return $this->error('刪除失敗，充電樁有使用該充電功率');
        }

        if (ChargingPile::query()->where('specification_id', $id)->exists()) {
            return $this->error('刪除失敗，充電樁有使用該充電規格');
        }

        if (!$item->delete()) {
            return $this->error(__('message.common.delete.fail'));
        }

        return $this->success(msg: __('message.common.delete.success'));
    }
}
