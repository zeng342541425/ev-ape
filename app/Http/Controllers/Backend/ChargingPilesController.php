<?php
namespace App\Http\Controllers\Backend;

use App\Exports\BaseExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\ChargingPiles\CreateRequest;
use App\Http\Requests\Backend\ChargingPiles\ListRequest;
use App\Http\Requests\Backend\ChargingPiles\IdRequest;
use App\Http\Requests\Backend\ChargingPiles\UpdateRequest;
use App\Http\Requests\Backend\ParkingLots\AuditRequest;
use App\Http\Requests\Backend\ParkingLots\FinalRequest;
use App\Models\Common\Appointment;
use App\Models\Common\Manufacturer;
use App\Models\Order\Order;
use App\Models\Parking\ChargingPile;
use App\Models\Parking\ChargingPileAuditLog;
use App\Models\Parking\ChargingPower;
use App\Models\Parking\ParkingLot;
use App\Services\Common\PileService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ChargingPilesController extends Controller
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

        $query = ChargingPile::query()->with(['parking' => function($query) {
            $query->with(['region', 'village']);
        }])->with('manufacturer:id,name');

        if (isset($param['search_words']) && $param['search_words'] != '') {
            $search_words = $param['search_words'];
            $query->where(function ($q) use($search_words) {
                $q->where('no', 'like', "%$search_words%");
                // $q->orWhere('serial_number', 'like', "%$search_words%");
            });

        }

        if (!empty($param['starting_time'])) {
            $starting_time = substr($param['starting_time'], 0, 10) . ' 00:00:00';
            $query->where('created_at', '>=', $starting_time);
        }

        if (!empty($param['ending_time'])) {
            $ending_time = substr($param['ending_time'], 0, 10) . ' 23:59:59';
            $query->where('created_at', '<=', $ending_time);
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

        if (isset($param['first_audit_status']) && is_numeric($param['first_audit_status']) && $param['first_audit_status'] >= 0) {
            $query->where('first_audit_status', $param['first_audit_status']);
        }

        if (isset($param['final_audit_status']) && is_numeric($param['final_audit_status']) && $param['final_audit_status'] >= 0) {
            $query->where('final_audit_status', $param['final_audit_status']);
        }

        if (isset($param['power_id']) && is_numeric($param['power_id']) && $param['power_id'] > 0) {
            $query->where('power_id', $param['power_id']);
        }

        if (isset($param['specification_id']) && is_numeric($param['specification_id']) && $param['specification_id'] > 0) {
            $query->where('specification_id', $param['specification_id']);
        }

        if (isset($param['parking_lot_id']) && is_numeric($param['parking_lot_id']) && $param['parking_lot_id'] > 0) {
            $query->where('parking_lot_id', '=', $param['parking_lot_id']);
        }
        if (isset($param['status']) && is_numeric($param['status']) && $param['status'] >= 0 ) {
            $query->where('status', '=', $param['status']);
        }

        if (isset($param['stat']) && is_numeric($param['stat']) && $param['stat'] >= 0 ) {
            $query->where('stat', '=', $param['stat']);
        }

        $query->orderByDesc('created_at');

        if ($paginate) {
            $list = $query->paginate($param['limit'] ?? 10);
            $data = $list->items();
        } else {
            $data = $query->get()->toArray();
        }

        if ($data) {
            $power_list = ChargingPower::query()->select('id', 'value')->get()->toArray();
            $power_map = [];
            foreach ($power_list as $v) {
                $power_map[$v['id']] = $v;
            }
            foreach($data as $k => $v) {

                $data[$k]['power_value'] = $power_map[$v['power_id']]['value'];
                $data[$k]['specification_value'] = $power_map[$v['specification_id']]['value'];

                $data[$k]['parking_fee'] = $v['parking']['parking_fee'];
                $data[$k]['parking_no'] = $v['parking']['no'];
                $data[$k]['parking_lot_name'] = $v['parking']['parking_lot_name'];
                $data[$k]['address'] = $v['parking']['address'];
                $data[$k]['region_name'] = $v['parking']['region']['name'];
                $data[$k]['village_name'] = $v['parking']['village']['name'];
                $data[$k]['manufacturer_name'] = $v['manufacturer']['name'] ?? '';
                // $data[$k]['images'] = json_decode($v['images'], true);

                // if ($v['audit_status'] != 1) {
                //     $data[$k]['stat'] = 0;
                // }

                unset($data[$k]['parking']);
                unset($data[$k]['manufacturer']);
            }
        }

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
            '充電站編號', '站點縣市', '縣市行政區', '充電站名稱', '充電樁廠商', '充電樁編號', '充電樁規格', '充電樁功率', '收費(元/每小時)',
            '充電樁狀態', '狀態', '初級審核狀態', '初級審核備註', '終級審核狀態', '終極審核備註'
        ];

        $data = [];
        if ($list) {
            $stat_map = [
                0 => '關閉',
                1 => '開啟',
            ];
            $status_map = [
                0 => '未使用',
                1 => '使用中',
                2 => '維修中',
            ];
            $audit_status_map = [
                0 => '待審核',
                1 => '通過',
                2 => '駁回',
            ];
            foreach($list as $v) {
                $data[] = [
                    $v['parking_no'],
                    $v['region_name'],
                    $v['village_name'],
                    $v['parking_lot_name'],
                    $v['manufacturer_name'],
                    $v['no'],
                    $v['specification_value'],
                    $v['power_value'],
                    $v['charging'],
                    $status_map[$v['status']] ?? '',
                    $stat_map[$v['stat']] ?? '',
                    $audit_status_map[$v['first_audit_status']] ?? '',
                    $v['first_audit_notes'],
                    $audit_status_map[$v['final_audit_status']] ?? '',
                    $v['final_audit_notes'],
                ];
            }
        }

        return Excel::download(new BaseExport($data, $headings), '充電樁.xlsx');
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

        $item = ChargingPile::query()->with(['parking' => function($query) {
            $query->with(['region', 'village']);
        }])->with('manufacturer:id,name')->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        $item['parking_fee'] = $item['parking']['parking_fee'];
        $item['address'] = $item['parking']['address'];
        $item['region_name'] = $item['parking']['region']['name'];
        $item['village_name'] = $item['parking']['village']['name'];
        $item['manufacturer_name'] = $item['manufacturer']['name'] ?? '';
        $item['parking_no'] = $item['parking']['no'];
        unset($item['parking']);
        unset($item['manufacturer']);

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
        Log::info('begin:');
        $param = $request->only([
            'parking_lot_id', 'no', 'serial_number', 'toll', 'charging', 'power_id', 'specification_id', 'preferential', 'stat', 'manufacturer_id'
        ]);

        $param['admin_id'] = Auth::id();

        if (ChargingPile::query()->where('no', $param['no'])->exists()) {
            return $this->error('充電樁編號已經存在');
        }

        if (ChargingPile::query()->where('serial_number', $param['serial_number'])->exists()) {
            return $this->error('機器號已經存在');
        }

        $power = ChargingPower::query()->where('id', $param['power_id'])->first();
        if (!$power) {
            return $this->error(__('message.data_not_found'));
        }

        $specification = ChargingPower::query()->where('id', $param['specification_id'])->first();
        if (!$specification) {
            return $this->error(__('message.data_not_found'));
        }

        $parking = ParkingLot::query()->where('id', $param['parking_lot_id'])->first();
        if (!$parking) {
            return $this->error(__('message.data_not_found'));
        }

        if (!empty($param['manufacturer_id'])) {
            $m_ex = Manufacturer::query()->where('id', $param['manufacturer_id'])->first();
            if (!$m_ex) {
                return $this->error('充電樁廠商未找到');
            }
        }

        // 驗證收費金額不能小於0 todo
        // 充電站停車費(A),充電樁收費(B),充電樁優惠收費(C),優惠扣除金額(D)
        //前台實際收費A+B-C-D=E
        // 防呆機制1：A+B>=C+D
        // 防呆機制2：B>C
        // 防呆機制3：A>D
        Log::info('code11111:');
        DB::beginTransaction();
        try {

            $this->_checkPrice($parking['parking_fee'], $param['charging'], $param['toll'], $param['preferential']);

            $r = ChargingPile::query()->create($param);
            if (!$r) {
                throw new Exception(__('message.common.create.fail'));
            }

            // todo 臨時關閉，上綫時打開
            $code = (new PileService())->register($param['serial_number']);
            Log::info('code:');
            if (!$code) {
                throw new Exception('充電樁機器號綁定失敗');
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
            'parking_lot_id', 'no', 'serial_number', 'stat', 'toll', 'charging', 'power_id', 'specification_id', 'preferential', 'manufacturer_id'
        ]);

        $item = ChargingPile::query()->find($id);

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

        if (ChargingPile::query()->where('no', $param['no'])->whereNot('id', $id)->exists()) {
            return $this->error('充電樁編號已經存在');
        }

        if (ChargingPile::query()->where('serial_number', $param['serial_number'])->whereNot('id', $id)->exists()) {
            return $this->error('機器號已經存在');
        }

        $power = ChargingPower::query()->where('id', $param['power_id'])->first();
        if (!$power) {
            return $this->error(__('message.data_not_found'));
        }

        $specification = ChargingPower::query()->where('id', $param['specification_id'])->first();
        if (!$specification) {
            return $this->error(__('message.data_not_found'));
        }

        $parking = ParkingLot::query()->where('id', $param['parking_lot_id'])->first();
        if (!$parking) {
            return $this->error(__('message.data_not_found'));
        }

        if (!empty($param['manufacturer_id'])) {
            $m_ex = Manufacturer::query()->where('id', $param['manufacturer_id'])->first();
            if (!$m_ex) {
                return $this->error('充電樁廠商未找到');
            }
        }

        DB::beginTransaction();
        try {

            $this->_checkPrice($parking['parking_fee'], $param['charging'], $param['toll'], $param['preferential']);

            if (!ChargingPile::query()->where('id', $id)->update($param)) {
                throw new Exception(__('message.common.update.fail'));
            }

            if ($item['audit_status'] == 1) {
                $this->_common($param['parking_lot_id']);

                if ($param['parking_lot_id'] != $item['parking_lot_id']) {
                    $this->_common($item['parking_lot_id']);
                }
            }

            if ($param['serial_number'] != $item['serial_number']) {
                $r = (new PileService())->register($param['serial_number']);
                if (!$r)  {
                    throw new Exception('充電樁機器號綁定失敗');
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
     * @throws Exception
     */
    protected function _common($parking_lot_id = 0)
    {

        $update_parking = [];

        $power_ids = ChargingPile::query()->select(DB::raw('distinct power_id'))
            ->where('parking_lot_id', $parking_lot_id)->get()->toArray();
        if ($power_ids) {
            $p = ChargingPower::query()->select('value')->whereIn('id', array_column($power_ids, 'power_id'))->get()->toArray();
            if ($p) {
                $p_str = implode(',', array_column($p, 'value'));
                $update_parking['power_values'] = $p_str;
            } else {
                $update_parking['power_values'] = '';
            }
        } else {
            $update_parking['power_values'] = '';
        }

        $specification_ids = ChargingPile::query()->select(DB::raw('distinct specification_id'))
            ->where('parking_lot_id', $parking_lot_id)->get()->toArray();
        if ($specification_ids) {
            $p = ChargingPower::query()->select('value')->whereIn('id', array_column($specification_ids, 'specification_id'))->get()->toArray();
            if ($p) {
                $p_str = implode(',', array_column($p, 'value'));
                $update_parking['specification_values'] = $p_str;
            } else {
                $update_parking['specification_values'] = '';
            }
        } else {
            $update_parking['specification_values'] = '';
        }

        // 收費方式: 充電樁最小值-最大值
        $select = [
            DB::raw('min(charging) min_charging'),
            DB::raw('max(charging) max_charging'),
            DB::raw('min(toll) min_toll'),
            DB::raw('max(toll) max_toll'),
            DB::raw('min(preferential) min_preferential'),
            DB::raw('max(preferential) max_preferential'),
        ];
        $charging_b = ChargingPile::query()->select( $select )
            ->where('parking_lot_id', $parking_lot_id)->first();
        if ($charging_b && isset($charging_b['min_charging']) && isset($charging_b['max_charging'])) {
            $min = $charging_b['min_charging'];
            $max = $charging_b['max_charging'];
            $update_parking['charging_range'] = $min;
            if ($max > $min) {
                $update_parking['charging_range'] = $min . '-' . $max;
            }
        } else {
            $update_parking['charging_range'] = '';
        }

        if ($charging_b && isset($charging_b['min_toll']) && isset($charging_b['max_toll'])) {
            $min = $charging_b['min_toll'];
            $max = $charging_b['max_toll'];
            $update_parking['toll_range'] = $min;
            if ($max > $min) {
                $update_parking['toll_range'] = $min . '-' . $max;
            }
        } else {
            $update_parking['toll_range'] = '';
        }

        if ($charging_b && isset($charging_b['min_preferential']) && isset($charging_b['max_preferential'])) {
            $min = $charging_b['min_preferential'];
            $max = $charging_b['max_preferential'];
            $update_parking['preferential_range'] = $min;
            if ($max > $min) {
                $update_parking['preferential_range'] = $min . '-' . $max;
            }
        } else {
            $update_parking['preferential_range'] = '';
        }

        if ($update_parking) {
            $r = ParkingLot::query()->where('id', $parking_lot_id)->update($update_parking);
            if (!$r) {
                throw new Exception(__('message.common.update.fail'));
            }
        }
    }

    /**
     * 防呆機制
     * @throws Exception
     */
    protected function _checkPrice($A, $B, $C, $D)
    {
        // 驗證收費金額不能小於0 todo
        // 充電站停車費(A),充電樁收費(B),充電樁優惠收費(C),優惠扣除金額(D)
        // 前台實際收費A+B-C-D=E
        // 防呆機制1：A+B>=C+D
        // 防呆機制2：B>C
        // 防呆機制3：A>D

        // 防呆機制1：A+B>=C+D
        if ($A + $B < $C + $D) {
            throw new Exception('充電站停車費和充電樁收費之和不能小於充電樁優惠收費和優惠扣除金額之和');
        }

        // 防呆機制2：B>C
        if ($B <= $C) {
            throw new Exception('充電樁收費須大於充電樁優惠收費');
        }

        // 防呆機制3：A>D
        if ($A <= $D) {
            throw new Exception('充電站停車費須大於優惠扣除金額');
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

        $item = ChargingPile::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        $order_info = Order::query()->select('id')->where('pile_id', $id)->first();
        if ($order_info) {
            return $this->error('充電記錄已存在該充電樁');
        }

        $order_info = Appointment::query()->select('id')->where('pile_id', $id)->first();
        if ($order_info) {
            return $this->error('預約記錄已存在該充電樁');
        }

        if (!$item->delete()) {
            return $this->error(__('message.common.delete.fail'));
        }

        return $this->success(msg: __('message.common.delete.success'));
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

        $item = ChargingPile::query()->find($id);

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
                'pile_id' => $id,
                'audit_status' => $param['first_audit_status'],
                'audit_admin_id' => $audit_admin_id,
                'audit_type' => 'first',
                'audit_notes' => $param['first_audit_notes'],
            ];
            $r = ChargingPileAuditLog::query()->create($log_data);
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

        $item = ChargingPile::query()->find($id);

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
                'pile_id' => $id,
                'audit_status' => $param['final_audit_status'],
                'audit_admin_id' => $audit_admin_id,
                'audit_type' => 'final',
                'audit_notes' => $param['final_audit_notes'],
            ];
            $r = ChargingPileAuditLog::query()->create($log_data);
            if (!$r) {
                return $this->error();
            }

            if ($param['audit_status'] == 1) {
                $this->_common($item['parking_lot_id']);
            }

            DB::commit();
            return $this->success();

        } catch (Throwable $e) {

            DB::rollBack();
            return $this->error($e->getMessage());
        }

    }
}
