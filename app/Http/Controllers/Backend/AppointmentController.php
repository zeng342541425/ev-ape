<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Appointment\CreateRequest;
use App\Http\Requests\Backend\Appointment\ListRequest;
use App\Http\Requests\Backend\Appointment\IdRequest;
use App\Http\Requests\Backend\Appointment\UpdateRequest;
use App\Models\Common\Appointment;
use App\Models\Parking\ParkingLot;
use Symfony\Component\HttpFoundation\Response;

class AppointmentController extends Controller
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

        $query = Appointment::query();

        $query->with(['parking' => function($q) {
            $q->select('id', 'parking_lot_name', 'region_id', 'village_id')->with(['region', 'village']);
        }]);

        $query->with('userinfo:id,name,phone');
        $query->with('cancellation');

        if (isset($param['search_words']) && $param['search_words'] != '') {
            $search_words = $param['search_words'];
            $query->where(function ($q) use($search_words) {
                $q->where('pile_no', 'like', "%$search_words%");
                // $q->orWhere('description', 'like', "%$search_words%");
                // $q->orWhere('notes', 'like', "%$search_words%");
            });

        }

        if (isset($param['parking_lot_id']) && is_numeric($param['parking_lot_id']) && $param['parking_lot_id'] > 0) {
            $query->where('parking_lot_id', $param['parking_lot_id']);
        }

        if (isset($param['user_id']) && is_numeric($param['user_id']) && $param['user_id'] > 0) {
            $query->where('user_id', $param['user_id']);
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

        if (isset($param['status']) && is_numeric($param['status'])) {
            $query->where('status', '=', $param['status']);
        }

        if (!empty($param['starting_time'])) {
            $starting_time = substr($param['starting_time'], 0, 10) . ' 00:00:00';
            $query->where('appointment_at', '>=', $starting_time);
        }

        if (!empty($param['ending_time'])) {
            $ending_time = substr($param['ending_time'], 0, 10) . ' 23:59:59';
            $query->where('appointment_at', '<=', $ending_time);
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
                $data[$k]['reason_title'] = $v['cancellation']['reason_title'] ?? '';
                $data[$k]['image_url'] = $v['cancellation']['image_url'] ?? '';
                $data[$k]['description'] = $v['cancellation']['description'] ?? '';
                $data[$k]['star'] = $v['star'] > 0 ? $v['star'] : '-';

                unset($data[$k]['userinfo'], $data[$k]['user_id']);
                unset($data[$k]['cancellation']);
                unset($data[$k]['parking']);
            }
        }

        return $this->success([
            'list' => $data,
            'total' => $list->total()
        ]);
    }


}
