<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Reporting\CreateRequest;
use App\Http\Requests\Backend\Reporting\ListRequest;
use App\Http\Requests\Backend\Reporting\IdRequest;
use App\Http\Requests\Backend\Reporting\UpdateRequest;
use App\Models\Common\Reporting;
use App\Models\Parking\ChargingPile;
use App\Models\Parking\ParkingLot;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ReportingController extends Controller
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

        $query = Reporting::query();
        $query->with(['parking' => function($q) {
            $q->select('id', 'parking_lot_name', 'region_id', 'village_id')->with(['region', 'village']);
        }]);

        if (isset($param['search_words']) && $param['search_words'] != '') {
            $search_words = $param['search_words'];
            $query->where(function ($q) use($search_words) {
                $q->where('pile_no', 'like', "%$search_words%");
                $q->orWhere('reason', 'like', "%$search_words%");
                $q->orWhere('notes', 'like', "%$search_words%");
                $q->orWhere('admin_name', 'like', "%$search_words%");
            });

        }

        if (isset($param['parking_lot_id']) && is_numeric($param['parking_lot_id']) && $param['parking_lot_id'] > 0) {
            $query->where('parking_lot_id', $param['parking_lot_id']);
        }

        if (isset($param['region_id']) && is_numeric($param['region_id']) && $param['region_id'] > 0
            && isset($param['village_id']) && is_numeric($param['village_id'])) {
            $model = ParkingLot::query()->select('id')->where('region_id', $param['region_id']);
            if ($param['village_id'] > 0) {
                $model->where('village_id', $param['village_id']);
            }
            $id_list = $model->get()->toArray();

            $ids = [0];
            if ($id_list) {
                $ids = array_column($id_list, 'id');
            }
            $query->whereIn('parking_lot_id', $ids);

        }

        if (isset($param['status']) && $param['status'] != '') {
            $query->where('status', '=', $param['status']);
        }

        if (!empty($param['starting_time'])) {
            $starting_time = substr($param['starting_time'], 0, 10) . ' 00:00:00';
            $query->where('created_at', '>=', $starting_time);
        }

        if (!empty($param['ending_time'])) {
            $ending_time = substr($param['ending_time'], 0, 10) . ' 23:59:59';
            $query->where('created_at', '<=', $ending_time);
        }

        if (!empty($param['repaired_starting_time'])) {
            $starting_time = substr($param['repaired_starting_time'], 0, 10) . ' 00:00:00';
            $query->where('repaired_at', '>=', $starting_time);
        }

        if (!empty($param['repaired_ending_time'])) {
            $ending_time = substr($param['repaired_ending_time'], 0, 10) . ' 23:59:59';
            $query->where('repaired_at', '<=', $ending_time);
        }

        $query->orderBy($param['sort'] ?: 'id', $param['order'] ?: 'desc');

        $list = $query->paginate($param['limit'] ?? 10);
        $data = $list->items();

        if ($data) {
            foreach($data as $k => $v) {

                $data[$k]['parking_lot_name'] = $v['parking']['parking_lot_name'] ?? '';
                $data[$k]['region_name'] = $v['parking']['region']['name'] ?? '';
                $data[$k]['village_name'] = $v['parking']['village']['name'] ?? '';

                unset($data[$k]['parking']);
            }
        }

        return $this->success([
            'list' => $data,
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

        $item = Reporting::query()->with(['parking' => function($q) {
            $q->select('id', 'parking_lot_name', 'region_id', 'village_id')->with(['region', 'village']);
        }])->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        $item['parking_lot_name'] = $item['parking']['parking_lot_name'] ?? '';
        $item['region_name'] = $item['parking']['region']['name'] ?? '';
        $item['village_name'] = $item['parking']['village']['name'] ?? '';

        unset($item['parking']);

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
            'pile_no', 'reason', 'notes'
        ]);

        $pile = ChargingPile::query()->where('no', $param['pile_no'])->first();
        if (!$pile) {
            return $this->error(__('message.data_not_found'));
        }

        $parking = ParkingLot::query()->where('id', $pile['parking_lot_id'])->first();
        if (!$parking) {
            return $this->error(__('message.data_not_found'));
        }

        $admin = Auth::user();
        $param['pile_id'] = $pile['id'];
        $param['parking_lot_id'] = $pile['parking_lot_id'];
        $param['status'] = 0;
        $param['admin_id'] = $admin['id'];
        $param['admin_name'] = $admin['name'];

        DB::beginTransaction();
        try {
            $item = Reporting::query()->create($param);
            if (!$item) {
                throw new Exception(__('message.common.create.fail'));
            }

            // 把充電樁置爲報修狀態
            $r = ChargingPile::query()->where('no', $param['pile_no'])->update([
                'status' => 2
            ]);
            if (!$r) {
                throw new Exception(__('message.common.create.fail'));
            }

            DB::commit();

            return $this->success();

        } catch (Throwable $e) {

            DB::rollBack();
            return $this->error($e->getMessage());
        }

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
            'status', 'notes',
        ]);

        $item = Reporting::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if ($param['status'] == 1) {
            $param['repaired_at'] = date('Y-m-d H:i:s');
        }

        DB::beginTransaction();
        try {

            if (!$item->update($param)) {
                return $this->error(__('message.common.update.fail'));
            }

            // 如果修復，充電樁置爲空閑狀態
            if ($param['status'] == 1) {
                $r = ChargingPile::query()->where('id', $item['pile_id'])->update([
                    'status' => 0
                ]);
                if (!$r) {
                    throw new Exception(__('message.common.create.fail'));
                }
            }

            DB::commit();

            return $this->success();

        } catch (Throwable $e) {

            DB::rollBack();
            return $this->error($e->getMessage());
        }
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

        $item = Reporting::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (!$item->delete()) {
            return $this->error(__('message.common.delete.fail'));
        }

        return $this->success(msg: __('message.common.delete.success'));
    }
}
