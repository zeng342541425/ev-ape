<?php
namespace App\Http\Controllers\Backend;

use App\Exports\BaseExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\ParkingLots\AuditRequest;
use App\Http\Requests\Backend\ParkingLots\CreateRequest;
use App\Http\Requests\Backend\ParkingLots\FinalRequest;
use App\Http\Requests\Backend\ParkingLots\ListRequest;
use App\Http\Requests\Backend\ParkingLots\IdRequest;
use App\Http\Requests\Backend\ParkingLots\UpdateRequest;
use App\Models\Order\Order;
use App\Models\Parking\ChargingPile;
use App\Models\Parking\Favorite;
use App\Models\Parking\ParkingLot;
use App\Models\Parking\ParkingLotAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ParkingLotsController extends Controller
{
    /**
     * 列表
     *
     * @param array $param
     * @param bool $paginate
     * @return array
     */
    public function _list(array $param=[], bool $paginate=true): array
    {

        $query = ParkingLot::query()->with(['region', 'village']);

        if (isset($param['search_words']) && $param['search_words'] != '') {
            $search_words = $param['search_words'];
            $query->where(function ($q) use($search_words) {
                $q->where('parking_lot_name', 'like', "%$search_words%");
                $q->orWhere('no', 'like', "%$search_words%");
                $q->orWhere('address', 'like', "%$search_words%");
            });

        }

        if (isset($param['region_id']) && is_numeric($param['region_id']) && $param['region_id'] > 0) {
            $query->where('region_id', '=', $param['region_id']);
        }

        if (isset($param['village_id']) && is_numeric($param['village_id']) && $param['village_id'] > 0) {
            $query->where('village_id', '=', $param['village_id']);
        }

        if (isset($param['status']) && is_numeric($param['status']) && $param['status'] >= 0) {
            $query->where('status', $param['status']);
        }

        if (isset($param['first_audit_status']) && is_numeric($param['first_audit_status']) && $param['first_audit_status'] >= 0) {
            $query->where('first_audit_status', $param['first_audit_status']);
        }

        if (isset($param['final_audit_status']) && is_numeric($param['final_audit_status']) && $param['final_audit_status'] >= 0) {
            $query->where('final_audit_status', $param['final_audit_status']);
        }

        if (!empty($param['starting_time'])) {
            $starting_time = substr($param['starting_time'], 0, 10) . ' 00:00:00';
            $query->where('created_at', '>=', $starting_time);
        }

        if (!empty($param['ending_time'])) {
            $ending_time = substr($param['ending_time'], 0, 10) . ' 23:59:59';
            $query->where('created_at', '<=', $ending_time);
        }

        $query->orderBy($param['sort'] ?: 'id', $param['order'] ?: 'desc');

        if ($paginate) {
            $list = $query->paginate($param['limit'] ?? 10);
            $data = $list->items();
        } else {
            $data = $query->get()->toArray();
        }

        if ($data) {
            $parking_ids = [0];
            foreach($data as $v) {
                if ($v['audit_status'] == 1) {
                    $parking_ids[] = $v['id'];
                }
            }

            // $parking_ids = array_column($data, 'id');
            $select = [
                'parking_lot_id',
                DB::raw("count(id) as total"),
                DB::raw("SUM(IF(`status`=0, 1, 0)) AS free_time_number"),
                DB::raw("SUM(IF(`status`=1, 1, 0)) AS busy_number"),
                DB::raw("SUM(IF(`status`=2, 1, 0)) AS fault_number"),
            ];
            $charging_list = ChargingPile::query()->select($select)
                ->whereIn('parking_lot_id', $parking_ids)
                ->groupBy('parking_lot_id')
                ->get()
                ->toArray();

            $charging_map_list = [];
            if ($charging_list) {
                foreach ($charging_list as $v) {
                    $charging_map_list[$v['parking_lot_id']] = $v;
                }
            }

            $select = [
                'parking_lot_id',
                DB::raw("count(id) as total"),
                DB::raw("SUM(star) AS total_start"),
            ];
            $start_list = Order::query()->select($select)->whereIn('parking_lot_id', $parking_ids)
                ->where('star', '>', 0)->groupBy('parking_lot_id')->get()->toArray();
            $start_map_list = [];
            if ($start_list) {
                foreach ($start_list as $v) {
                    $start_map_list[$v['parking_lot_id']] = $v;
                }
            }

            foreach($data as $k => $v) {
                $total = $charging_map_list[$v['id']]['total'] ?? 0;
                $free_time_number = $charging_map_list[$v['id']]['free_time_number'] ?? 0;
                $busy_number = $charging_map_list[$v['id']]['busy_number'] ?? 0;
                $fault_number = $charging_map_list[$v['id']]['fault_number'] ?? 0;

                $data[$k]['star'] = '-';
                if (isset($start_map_list[$v['id']]) && isset($start_map_list[$v['id']]['total']) && $start_map_list[$v['id']]['total'] > 0) {
                    $start_total_number = $start_map_list[$v['id']]['total'];
                    $total_start = $start_map_list[$v['id']]['total_start'];
                    $data[$k]['star'] = strval(round($total_start/$start_total_number, 2));
                }

                $data[$k]['total_number'] = $total;
                $data[$k]['free_time_number'] = $free_time_number;
                $data[$k]['busy_number'] = $busy_number;
                $data[$k]['fault_number'] = $fault_number;
                $data[$k]['region_name'] = $v['region']['name'];
                $data[$k]['village_name'] = $v['village']['name'];
                $data[$k]['images'] = json_decode($v['images'], true);

                // if ($v['audit_status'] != 1) {
                //     $data[$k]['status'] = 0;
                // }

                unset($data[$k]['region'], $data[$k]['village'], $data[$k]['power_values'], $data[$k]['specification_values']);
            }
        }

        // return $this->success([
        //     'list' => $data,
        //     'total' => $list->total()
        // ]);

        if ($paginate) {
            return [
                'list' => $data,
                'total' => $list->total()
            ];
        } else {
            return $data;
        }

    }

    /**
     * 列表
     *
     * @param ListRequest $request
     * @return Response
     */
    public function list(ListRequest $request): Response
    {
        $param = $request->all();

        return $this->success($this->_list($param));
    }

    // 匯出資料
    public function export(Request $request): BinaryFileResponse
    {
        $param = $request->all();
        $list = $this->_list($param, false);

        $headings = [
            '充電站編號', '站點縣市', '縣市行政區', '充電站名稱', '地址', '營業時間', '停車費率', '備註', '狀態', '加入時間',
            '充電樁總數', '充電樁空位數', '充電樁使用數', '充電樁報修數', '評分', '初級審核狀態', '初級審核備註', '終級審核狀態', '終極審核備註'
        ];

        $data = [];
        if ($list) {
            $status_map = [
                0 => '關閉',
                1 => '開啟',
            ];
            $audit_status_map = [
                0 => '待審核',
                1 => '通過',
                2 => '駁回',
            ];
            foreach($list as $v) {
                $data[] = [
                    $v['no'],
                    $v['region_name'],
                    $v['village_name'],
                    $v['parking_lot_name'],
                    $v['address'],
                    $v['business_hours'],
                    $v['parking_fee'],
                    $v['notes'],
                    $status_map[$v['status']] ?? '',
                    $v['created_at'],
                    $v['total_number'],
                    $v['free_time_number'],
                    $v['busy_number'],
                    $v['fault_number'],
                    $v['star'],
                    $audit_status_map[$v['first_audit_status']] ?? '',
                    $v['first_audit_notes'],
                    $audit_status_map[$v['final_audit_status']] ?? '',
                    $v['final_audit_notes'],
                ];
            }
        }

        return Excel::download(new BaseExport($data, $headings), '場域.xlsx');

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

        $item = ParkingLot::query()->with(['region', 'village'])->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        $item['images'] = json_decode($item['images'], true);
        $item['region_name'] = $item['region']['name'];
        $item['village_name'] = $item['village']['name'];
        unset($item['region']);
        unset($item['village']);

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
            'no', 'parking_lot_name', 'region_id', 'village_id', 'address', 'status', 'latitude', 'longitude',
            'business_hours', 'parking_fee','images', 'notes'
        ]);

        if (count($param['images']) > 5) {
            return $this->error('圖檔超過5張');
        }

        $param['images'] = json_encode($param['images']);

        $param['admin_id'] = Auth::id();

        if (ParkingLot::query()->where('no', $param['no'])->exists()) {
            return $this->error('充電站編號已經存在');
        }

        $item = ParkingLot::query()->create($param);
        if (!$item) {
            return $this->error();
        }

        // $item['images'] = json_decode($item['images'], true);
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
            'no', 'parking_lot_name', 'region_id', 'village_id', 'address', 'status', 'latitude', 'longitude',
            'business_hours', 'parking_fee','images', 'notes'
        ]);

        if (count($param['images']) > 5) {
            return $this->error('圖檔超過5張');
        }

        if (ParkingLot::query()->where('no', $param['no'])->whereNot('id', $id)->exists()) {
            return $this->error('充電站編號已經存在');
        }

        $item = ParkingLot::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if ($item['audit_status'] == 0) {
            return $this->error('審核進行中，編輯失敗');
        }

        if ($item['audit_status'] == 2) {
            $param['audit_status'] = 0;
            $param['first_audit_status'] = 0;
            $param['final_audit_status'] = 0;
            $param['first_audit_admin_id'] = 0;
            $param['final_audit_admin_id'] = 0;
        }

        $param['images'] = json_encode($param['images']);
        if (!$item->update($param)) {
            return $this->error(__('message.common.update.fail'));
        }

        $item['images'] = json_decode($item['images'], true);
        return $this->success(null, __('message.common.update.success'));
    }

    /**
     * 初級審核
     *
     * @param AuditRequest $request
     * @return Response
     */
    public function audit(AuditRequest $request): Response
    {
        $id = $request->post('id');
        $param = $request->only([
            'first_audit_status',
            'audit_notes'
        ]);

        $item = ParkingLot::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if ($item['first_audit_status'] != 0) {
            return $this->error('初級審核狀態必須待審核');
        }

        $audit_admin_id = Auth::id();

        $param['first_audit_notes'] = $param['audit_notes'] ?? '';
        unset($param['audit_notes']);
        $param['first_audit_admin_id'] = $audit_admin_id;
        $param['first_audited_at'] = date('Y-m-d H:i:s');

        if ($param['first_audit_status'] == 2) {
            $param['audit_status'] = 2;
        }

        DB::beginTransaction();
        try {

            if (!$item->update($param)) {
                return $this->error();
            }

            $log_data = [
                'parking_lot_id' => $id,
                'audit_status' => $param['first_audit_status'],
                'audit_admin_id' => $audit_admin_id,
                'audit_type' => 'first',
                'audit_notes' => $param['first_audit_notes'],
            ];
            $r = ParkingLotAuditLog::query()->create($log_data);
            if (!$r) {
                return $this->error();
            }

            DB::commit();
            return $this->success();

        } catch (Throwable $e) {

            DB::rollBack();
            return $this->error($e->getMessage());
        }

    }

    /**
     * 終級審核
     *
     * @param FinalRequest $request
     * @return Response
     */
    public function final(FinalRequest $request): Response
    {
        $id = $request->post('id');
        $param = $request->only([
            'final_audit_status',
            'audit_notes'
        ]);

        $item = ParkingLot::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if ($item['first_audit_status'] != 1) {
            return $this->error('初級審核狀態必須核准');
        }

        if ($item['final_audit_status'] != 0) {
            return $this->error('終級審核狀態必須待審核');
        }

        $audit_admin_id = Auth::id();

        $param['final_audit_notes'] = $param['audit_notes'] ?? '';
        unset($param['audit_notes']);

        $param['final_audit_admin_id'] = $audit_admin_id;
        $param['final_audited_at'] = date('Y-m-d H:i:s');

        if ($param['final_audit_status'] == 2) {
            $param['audit_status'] = 2;
        } else {
            $param['audit_status'] = 1;
        }

        DB::beginTransaction();
        try {

            if (!$item->update($param)) {
                return $this->error();
            }

            $log_data = [
                'parking_lot_id' => $id,
                'audit_status' => $param['final_audit_status'],
                'audit_admin_id' => $audit_admin_id,
                'audit_type' => 'final',
                'audit_notes' => $param['final_audit_notes'],
            ];
            $r = ParkingLotAuditLog::query()->create($log_data);
            if (!$r) {
                return $this->error();
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

        $item = ParkingLot::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (ChargingPile::query()->where('parking_lot_id', $id)->exists()) {
            return $this->error('此充電站已有充電樁紀錄，無法刪除');
        }

        if (Favorite::query()->where('parking_lot_id', $id)->exists()) {
            return $this->error('已有車主關注，無法刪除');
        }

        if (!$item->delete()) {
            return $this->error(__('message.common.delete.fail'));
        }

        return $this->success(msg: __('message.common.delete.success'));
    }
}
