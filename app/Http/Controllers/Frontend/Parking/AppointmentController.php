<?php

namespace App\Http\Controllers\Frontend\Parking;

use App\Http\Controllers\Frontend\BaseController;
use App\Http\Requests\Frontend\Parking\Charging\AppointmentCancelRequest;
use App\Http\Requests\Frontend\Parking\Charging\AppointmentRequest;
use App\Jobs\RegularPushJob;
use App\Models\Common\Appointment;
use App\Models\Common\AppointmentCancellation;
use App\Models\Common\AppointmentReason;
use App\Models\Parking\ChargingPile;
use App\Models\Parking\ParkingLot;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;


class AppointmentController extends BaseController
{

    /**
     * 預約詳情
     *
     * @param Request $request
     * @return Response
     */
    public function detail(Request $request): Response
    {

        $user = $request->user();

        $longitude = $request->get('longitude', config('evape.default_longitude'));
        $latitude = $request->get('latitude', config('evape.default_latitude'));

        $info = Appointment::query()->with(['parking' => function($q) use($longitude, $latitude) {
            $q->select('id', 'parking_lot_name', 'longitude', 'latitude',
                DB::raw("ST_DISTANCE(ST_GeomFromText('POINT({$longitude} {$latitude})'), POINT(longitude, latitude))*111195 AS distance")
            );
        }])->where('user_id', $user['id'])
            ->where('expired_at', '>', date('Y-m-d H:i:s'))
            ->where('status', 0)
            ->orderBy('expired_at', 'desc')
            ->first();

        if ($info) {

            $info['parking_lot_name'] = $info['parking']['parking_lot_name'];
            $info['longitude'] = $info['parking']['longitude'];
            $info['latitude'] = $info['parking']['latitude'];
            $info['distance'] = $info['parking']['distance'];

            // if ($info['status'] == 0 && strtotime($info['expired_at']) < time()) {
            //     $info['status'] = 3;
            // }
            unset($info['parking']);
            unset($info['user_id']);
            unset($info['parking_lot_id']);
            unset($info['pile_id']);
            unset($info['expired_at']);
            unset($info['status']);

        }

        return $this->success([
            'info' => $info ?: new \stdClass(),
        ]);

    }

    /**
     * 開始充電
     * @param AppointmentRequest $request
     * @return Response
     */
    public function submit(AppointmentRequest $request): Response
    {

        $user = $request->user();

        $parking_lot_id = $request->get('parking_lot_id');
        $appointment_datetime = $request->get('appointment_at');
        $specification_id = $request->get('specification_id', 0);
        $power_id = $request->get('power_id', 0);

        $appointment_timestamp = strtotime($appointment_datetime);
        if ($appointment_timestamp <= time()) {
            return $this->error('預約時間不能小於當前時間');
        }

        if ($appointment_timestamp >= time() + 24 * 60 * 60) {
            return $this->error('預約時間只能24小時內');
        }

        // 充電預約狀態
        if ($user['appointment_status'] == 0) {
            return $this->error('充電預約功能關閉');
        }

        // 一個帳號可同時有擁有一筆預約
        $pre_date = date('Y-m-d H:i:s', strtotime('-1 day'));
        if (Appointment::query()->where('user_id', $user['id'])->where('appointment_at', '>=', $pre_date)->where('status', 0)->exists()) {
            return $this->error('一個帳號24小時內只能有一筆預約');
        }

        $parking_info = ParkingLot::query()->where('id', $parking_lot_id)->first();
        if (!$parking_info || $parking_info['status'] == 0) {
            return $this->error('設備暫停使用');
        }

        $charging_model = ChargingPile::query()
            ->where('parking_lot_id', $parking_lot_id)
            ->where('status', 0)
            ->where('stat', 1);

        if ($power_id > 0) {
            $charging_model->where('power_id', $power_id);
        }

        if ($specification_id > 0) {
            $charging_model->where('specification_id', $specification_id);
        }

        $info = $charging_model->orderByRaw('RAND()')->first();
        if (!$info) {
            return $this->error('沒有充電樁可預約');
        }

        $expired_minutes = config('evape.expired_minutes');

        DB::beginTransaction();

        try {
            $current_date = date('Y-m-d H:i:s');
            $appointment_id = Appointment::query()->insertGetId([
                'user_id' => $user['id'],
                'pile_id' => $info['id'],
                'pile_no' => $info['no'],
                'parking_lot_id' => $parking_lot_id,
                'appointment_at' => $appointment_datetime,
                'expired_at' => date('Y-m-d H:i:s', ($appointment_timestamp + $expired_minutes * 60)),
                'created_at' => $current_date,
                'updated_at' => $current_date,
            ]);
            if (!$appointment_id) {
                throw new Exception('預約失敗');
            }

            DB::commit();

            // 推播
            $key = 'appointment';
            RegularPushJob::dispatch($user['id'], $key);

            return $this->success([
                'appointment_id' => $appointment_id,
                'no' => $info['no'],
                'charging' => $info['charging'],
            ]);

        } catch (Throwable $e) {

            DB::rollBack();
            return $this->error($e->getMessage());
        }

    }

    /**
     * 取消預約
     *
     * @param AppointmentCancelRequest $request
     * @return Response
     */
    public function cancel(AppointmentCancelRequest $request): Response
    {

        $user = $request->user();

        $appointment_id = $request->get('appointment_id');
        $reason_id = $request->get('reason_id');
        $description = $request->get('description');
        $image = $request->get('image_url');

        $reason = AppointmentReason::query()->where('id', $reason_id)->first();
        if (!$reason) {
            return $this->error('請重新進入後提交');
        }

        $appoint = Appointment::query()->where('user_id', $user['id'])->where('id', $appointment_id)->where('status', 0)->first();
        if (!$appoint) {
            return $this->error('不用重複取消');
        }

        if ($description && mb_strlen($description) > 5000) {
            return $this->error('描述太長');
        }

        if ($image && mb_strlen($image) > 1000) {
            return $this->error('僅能上傳一張圖片');
        }

        DB::beginTransaction();

        try {
            $r = Appointment::query()->where('user_id', $user['id'])->where('id', $appointment_id)->update([
                'status' => 2
            ]);
            if (!$r) {
                return $this->error('請重新進入後提交');
            }

            $create_data = [
                'appointment_id' => $appointment_id,
                'reason_id' => $appointment_id,
                'reason_title' => $reason['title'],
            ];

            if ($description) {
                $create_data['description'] = $description;
            }

            if ($image) {
                $create_data['image_url'] = $image;
            }

            AppointmentCancellation::query()->create($create_data);

            DB::commit();

            return $this->success();
        } catch (Throwable $e) {

            DB::rollBack();
            return $this->error($e->getMessage());
        }

    }

    /**
     * 預約列表
     *
     * @param Request $request
     * @return Response
     */
    public function list(Request $request): Response
    {

        $request->validate([
            'page' => ['integer', 'min:1'],
            'limit' => ['integer', 'min:1'],
        ]);

        $param = $request->only(['limit']);

        $user = $request->user();

        $list = Appointment::query()->with(['parking' => function($q) {
            $q->select('id', 'parking_lot_name', 'longitude', 'latitude');
        }])->where('user_id', $user['id'])
            ->orderBy('appointment_at', 'desc')
            ->paginate($param['limit'] ?? 10);

        $data = $list->items();
        if ($data) {
            foreach($data as $k => $v) {
                $data[$k]['parking_lot_name'] = $v['parking']['parking_lot_name'];
                $data[$k]['longitude'] = $v['parking']['longitude'];
                $data[$k]['latitude'] = $v['parking']['latitude'];

                if ($v['status'] == 0 && strtotime($v['expired_at']) < time()) {
                    $data[$k]['status'] = 3;
                }
                unset($data[$k]['parking']);
                unset($data[$k]['user_id']);
                unset($data[$k]['parking_lot_id']);
                unset($data[$k]['pile_id']);
                unset($data[$k]['expired_at']);
            }
        }

        return $this->success([
            'list' => $list->items(),
            'total' => $list->total()
        ]);

    }

    /**
     * 取消預約原因列表
     *
     * @return Response
     */
    public function reasons(): Response
    {

        $list = AppointmentReason::query()->select('id', 'title')->get()->toArray();

        return $this->success([
            'list' => $list
        ]);

    }


}
