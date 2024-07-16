<?php
namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\DiningHotel\CreateRequest;
use App\Http\Requests\Backend\DiningHotel\ListRequest;
use App\Http\Requests\Backend\DiningHotel\IdRequest;
use App\Http\Requests\Backend\DiningHotel\UpdateIntroduceRequest;
use App\Http\Requests\Backend\DiningHotel\UpdateKnowRequest;
use App\Http\Requests\Backend\DiningHotel\UpdateRequest;
use App\Http\Requests\Backend\ParkingLots\AuditRequest;
use App\Http\Requests\Backend\ParkingLots\FinalRequest;
use App\Models\Common\DiningBooking;
use App\Models\Common\DiningHotel;
use App\Models\Common\DiningHotelAuditLog;
use App\Models\Common\DiningSeat;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DiningHotelController extends Controller
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

        $query = DiningHotel::query()->with('seat_info:id,dining_hotel_id,time,seats,charging');

        if (isset($param['search_words']) && $param['search_words'] != '') {
            $search_words = $param['search_words'];
            $query->where(function ($q) use($search_words) {
                $q->where('name', 'like', "%$search_words%");
            });

        }

        if (isset($param['status']) && is_numeric($param['status'])) {
            $query->where('status', '=', $param['status']);
        }
        if (isset($param['first_audit_status']) && is_numeric($param['first_audit_status'])) {
            $query->where('first_audit_status', '=', $param['first_audit_status']);
        }
        if (isset($param['final_audit_status']) && is_numeric($param['final_audit_status'])) {
            $query->where('final_audit_status', '=', $param['final_audit_status']);
        }

        if (isset($param['type_id']) && is_numeric($param['type_id'])) {
            $query->where('type_id', '=', $param['type_id']);
        }

        $query->orderBy('sequencing', $param['order'] ?: 'asc');

        $list = $query->paginate($param['limit']);

        $l = $list->items();
        // if ($l) {
        //     foreach($l as $k => $v) {
        //         if ($v['audit_status'] != 1) {
        //             $l[$k]['status'] = 0;
        //         }
        //     }
        // }

        return $this->success([
            'list' => $l,
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

        $item = DiningHotel::query()->with('seat_info:id,dining_hotel_id,time,seats,charging')->with(['region', 'village'])->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (!empty($item['filter_days'])) {
            $item['filter_days'] = explode(',', $item['filter_days']);
        } else {
            $item['filter_days'] = [];
        }
        $item['region_name'] = $item['region']['name']??'';
        $item['village_name'] = $item['village']['name']??'';
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
            'name', 'logo', 'address', 'starting_time', 'ending_time', 'cancel_days', 'status', 'notes', 'filter_days', 'sequencing','type_id','region_id','village_id'
        ]);

        $param['starting_time'] = substr($param['starting_time'], 0, 10) . ' 00:00:00';
        $param['ending_time'] = substr($param['ending_time'], 0, 10) . ' 23:59:59';
        $param['admin_id'] = Auth::id();

        $seat_info = $request->get('seat_info');

        DB::beginTransaction();
        try {

            $this->_check($param);

            if (!empty($param['filter_days'])) {
                $param['filter_days'] = array_unique($param['filter_days']);
                sort($param['filter_days']);
                $param['filter_days'] = implode(',', $param['filter_days']);
            }  else {
                $param['filter_days'] = null;
            }

            $item = DiningHotel::query()->create($param);
            if (!$item) {
                Log::info("新建餐旅基本設定失敗", ['data' => $param]);
                throw new Exception('新建失敗', 202);
            }

            $dining_hotel_id = $item['id'];
            foreach($seat_info as $v) {
                if (!isset($v['time'])) {
                    throw new Exception('開放時段不正確', 202);
                }

                $time_array = explode(':', $v['time']);
                if (count($time_array) != 2 || !is_numeric($time_array[0]) || !is_numeric($time_array[1])) {
                    throw new Exception('開放時段不正確', 202);
                }

                if (!isset($v['seats']) || !is_numeric($v['seats']) || $v['seats'] <= 0) {
                    throw new Exception('開放預約人數不正確', 202);
                }

                if (!isset($v['charging']) || !is_numeric($v['charging']) || $v['charging'] < 0) {
                    throw new Exception('預約時段收費(1人)不正確', 202);
                }

                $t = [
                    'dining_hotel_id' => $dining_hotel_id,
                    // 'type' => $v['type'],
                    'time' => $v['time'],
                    'seats' => $v['seats'],
                    'charging' => $v['charging'],
                ];
                $r = DiningSeat::query()->create($t);
                if (!$r) {
                    Log::info("新建開放預約失敗", ['data' => $t]);
                    throw new Exception('新建失敗', 202);
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
    protected function _check(array $data = [])
    {

        $starting_time = str_replace('-', '', substr($data['starting_time'], 0, 10));
        $ending_time = str_replace('-', '', substr($data['ending_time'], 0, 10));
        // if ($starting_time < date('Ymd')) {
        //     throw new Exception('預約開放開始期限不能小於當前時間', 202);
        // }

        if (strtotime($data['ending_time']) < time()) {
            throw new Exception('預約開放結束期限不能小於當前時間', 202);
        }

        if (strtotime($data['ending_time']) < strtotime($data['starting_time'])) {
            throw new Exception('預約開放開始期限不能小於結束期限', 202);
        }

        // filter_days
        if (!empty($data['filter_days'])) {
            foreach($data['filter_days'] as $v) {
                $s = str_replace('-', '', substr($v, 0, 10));
                if ($ending_time >= $s) {
                    continue;
                }

                throw new Exception('無法預約日期須小於預約開放結束期限', 202);
            }
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

        $dining_hotel_id = $id = $request->post('id');
        $param = $request->only([
            'name', 'logo', 'address', 'starting_time', 'ending_time', 'cancel_days', 'status', 'notes', 'filter_days', 'sequencing','type_id','region_id','village_id'
        ]);

        $seat_info = $request->get('seat_info');

        $param['starting_time'] = substr($param['starting_time'], 0, 10) . ' 00:00:00';
        $param['ending_time'] = substr($param['ending_time'], 0, 10) . ' 23:59:59';

        $item = DiningHotel::query()->find($id);

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

        DB::beginTransaction();
        try {

            $this->_check($param);
            if (!empty($param['filter_days'])) {
                $param['filter_days'] = array_unique($param['filter_days']);
                sort($param['filter_days']);
                $param['filter_days'] = implode(',', $param['filter_days']);
            } else {
                $param['filter_days'] = null;
            }

            if (!$item->update($param)) {
                Log::info("編輯餐旅設定失敗", ['data' => $param]);
                throw new Exception('編輯餐旅設定失敗', 202);
            }

            $exists_map = DiningSeat::query()->where('dining_hotel_id', $dining_hotel_id)->pluck('id','id')->toArray();


            foreach($seat_info as $v) {
                $seat_id = $v['id'] ?? 0;

                if (!isset($v['time'])) {
                    throw new Exception('開放時段不正確', 202);
                }

                $time_array = explode(':', $v['time']);
                if (count($time_array) != 2 || !is_numeric($time_array[0]) || !is_numeric($time_array[1])) {
                    throw new Exception('開放時段不正確', 202);
                }

                if (!isset($v['seats']) || !is_numeric($v['seats']) || $v['seats'] <= 0) {
                    throw new Exception('開放預約人數不正確', 202);
                }

                if (!isset($v['charging']) || !is_numeric($v['charging']) || $v['charging'] < 0) {
                    throw new Exception('預約時段收費(1人)不正確', 202);
                }

                if ($seat_id > 0 && isset($exists_map[$seat_id])) {
                    $t = [
                        'time' => $v['time'],
                        'seats' => $v['seats'],
                        'charging' => $v['charging'],
                    ];
                    $r = DiningSeat::query()->where('id', $seat_id)->update($t);
                    unset($exists_map[$seat_id]);
                } else {
                    $t = [
                        'dining_hotel_id' => $dining_hotel_id,
                        // 'type' => $v['type'],
                        'time' => $v['time'],
                        'seats' => $v['seats'],
                        'charging' => $v['charging'],
                    ];
                    $r = DiningSeat::query()->create($t);
                }

                if (!$r) {
                    Log::info("編輯開放預約失敗", ['data' => $v]);
                    throw new Exception('編輯失敗', 202);
                }
            }



            // 刪除的
            if ($exists_map) {

                if (DiningBooking::query()->whereIn('seat_id',$exists_map)->exists()){
                    throw new Exception('時間段已被預定', 202);
                }

                DiningSeat::query()->whereIn('id', $exists_map)->delete();
            }
            // throw new Exception('編輯餐旅設定失敗', 202);
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

        $item = DiningHotel::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (DiningBooking::query()->where('dining_hotel_id', $id)->exists()) {
            return $this->error('已存在預約記錄，無法刪除');
        }

        if (!$item->delete()) {
            return $this->error(__('message.common.delete.fail'));
        }

        DiningSeat::query()->where('dining_hotel_id', $id)->delete();

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

        $item = DiningHotel::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if ($item['first_audit_status'] != 0) {
            return $this->error('初級審核狀態必須待審核');
        }

        $audit_admin_id = Auth::id();

        $param['first_audit_admin_id'] = $audit_admin_id;

        $param['first_audit_notes'] = $param['audit_notes'] ?? '';
        unset($param['audit_notes']);

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
                'dining_hotel_id' => $id,
                'audit_status' => $param['first_audit_status'],
                'audit_admin_id' => $audit_admin_id,
                'audit_type' => 'first',
                'audit_notes' => $param['first_audit_notes'],
            ];
            $r = DiningHotelAuditLog::query()->create($log_data);
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

        $item = DiningHotel::query()->find($id);

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

        $param['final_audit_admin_id'] = $audit_admin_id;

        $param['final_audit_notes'] = $param['audit_notes'] ?? '';
        unset($param['audit_notes']);

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
                'dining_hotel_id' => $id,
                'audit_status' => $param['final_audit_status'],
                'audit_admin_id' => $audit_admin_id,
                'audit_type' => 'final',
                'audit_notes' => $param['final_audit_notes'],
            ];
            $r = DiningHotelAuditLog::query()->create($log_data);
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
     * 更新餐廳介紹
     *
     * @param UpdateIntroduceRequest $request
     * @return Response
     */
    public function updateIntroduce(UpdateIntroduceRequest $request): Response
    {
        $id = $request->post('id');
        $param = $request->only([
            'introduce',
        ]);

        $item = DiningHotel::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (!$item->update($param)) {
            Log::info("更新餐廳介紹失敗", ['data' => $param]);
            return $this->error('更新餐廳介紹失敗');
        }

        return $this->success();

    }

    /**
     * 更新預約需知
     *
     * @param UpdateKnowRequest $request
     * @return Response
     */
    public function updateKnow(UpdateKnowRequest $request): Response
    {
        $id = $request->post('id');
        $param = $request->only([
            'things_to_know',
        ]);

        $item = DiningHotel::query()->find($id);

        if (!$item) {
            return $this->error(__('message.data_not_found'));
        }

        if (!$item->update($param)) {
            Log::info("更新預約需知失敗", ['data' => $param]);
            return $this->error('更新預約需知失敗');
        }

        return $this->success();

    }


}
