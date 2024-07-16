<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Faults\CreateRequest;
use App\Http\Requests\Backend\Faults\ListRequest;
use App\Http\Requests\Backend\Faults\IdRequest;
use App\Http\Requests\Backend\Faults\UpdateRequest;
use App\Models\Common\Faults;
use App\Models\Parking\ParkingLot;
use Symfony\Component\HttpFoundation\Response;

class FaultsController extends Controller
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

        $query = Faults::query()->with('userinfo:id,name,phone');
        $query->with(['parking' => function($q) {
            $q->select('id', 'parking_lot_name', 'region_id', 'village_id')->with(['region', 'village']);
        }]);

        if (isset($param['search_words']) && $param['search_words'] != '') {
            $search_words = $param['search_words'];
            $query->where(function ($q) use($search_words) {
                $q->where('pile_no', 'like', "%$search_words%");
                $q->orWhere('description', 'like', "%$search_words%");
                $q->orWhere('notes', 'like', "%$search_words%");
                $q->orWhere('user_phone', 'like', "%$search_words%");
                $q->orWhere('user_name', 'like', "%$search_words%");
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

        if (isset($param['status']) && is_numeric($param['status']) && $param['status'] >= 0) {
            $query->where('status', $param['status']);
        }

        if (isset($param['category_id']) && is_numeric($param['category_id']) && $param['category_id'] > 0) {
            $query->where('category_id', $param['category_id']);
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
                $data[$k]['phone'] = $v['userinfo']['phone'] ?? '';
                $data[$k]['name'] = $v['userinfo']['name'] ?? '';
                $data[$k]['parking_lot_name'] = $v['parking']['parking_lot_name'] ?? '';
                $data[$k]['region_name'] = $v['parking']['region']['name'] ?? '';
                $data[$k]['village_name'] = $v['parking']['village']['name'] ?? '';

                unset($data[$k]['userinfo'], $data[$k]['user_id']);
                unset($data[$k]['images']);
                unset($data[$k]['parking']);
                unset($data[$k]['category_id']);
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

        $item = Faults::query()->with('userinfo:id,name,phone')->with(['parking' => function($q) {
            $q->select('id', 'parking_lot_name', 'region_id', 'village_id')->with(['region', 'village']);
        }])->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if ($item['images']) {
            $item['images'] = json_decode($item['images'], true);
        }

        $item['phone'] = $item['userinfo']['phone'] ?? '';
        $item['name'] = $item['userinfo']['name'] ?? '';
        $item['parking_lot_name'] = $item['parking']['parking_lot_name'] ?? '';
        $item['region_name'] = $item['parking']['region']['name'] ?? '';
        $item['village_name'] = $item['parking']['village']['name'] ?? '';

        unset($item['userinfo'], $item['user_id']);
        unset($item['parking']);
        unset($item['category_id']);

        return $this->success([
            'item' => $item
        ]);
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

        $item = Faults::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if ($item['status'] == 2) {
            return $this->error('已解決，不能提交');
        }

        if ($param['status'] == 2) {
            $param['repaired_at'] = date('Y-m-d H:i:s');
        }

        if (!$item->update($param)) {
            return $this->error(__('message.common.update.fail'));
        }

        return $this->success();
    }


}
