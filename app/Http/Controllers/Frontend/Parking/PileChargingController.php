<?php

namespace App\Http\Controllers\Frontend\Parking;

use App\Http\Controllers\Frontend\BaseController;
use App\Http\Requests\Frontend\Parking\Charging\ChargingRequest;
use App\Http\Requests\Frontend\Parking\Charging\PileRequest;
use App\Http\Requests\Frontend\Parking\Charging\ScoreRequest;
use App\Jobs\Payment;
use App\Jobs\Wrokerman;
use App\Models\Common\Appointment;
use App\Models\Common\InvoiceDonation;
use App\Models\Common\OrderCard;
use App\Models\Common\PileError;
use App\Models\Order\Order;
use App\Models\Parking\Brand;
use App\Models\Parking\ChargingPile;
use App\Models\Parking\ParkingLot;
use App\Models\User\CreditCard;
use App\Models\User\Invoice;
use App\Services\Common\Common;
use App\Services\Common\PaymentService;
use App\Services\Common\TapPayService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * 充電樁交互api
 */
class PileChargingController extends BaseController
{

    /**
     * 開始充電
     * @param ChargingRequest $request
     * @return Response
     */
    public function starting(Request $request): Response
    {

        $request->validate(
            [
                'order_id' => ['required', 'integer'],
                'serial_number' => ['required'],
                'starting_datetime' => ['required', 'date'],
                'tap_datetime' => ['required', 'date'],
            ],
            [
                'order_id.required' => '充電記錄ID必須',
                'order_id.integer' => '充電記錄ID不正確',
                'starting_datetime.required' => 'starting_datetime 必須',
                'starting_datetime.date' => 'starting_datetime 不正確',
                'tap_datetime.required' => 'tap_datetime 必須',
                'tap_datetime.date' => 'tap_datetime 不正確',
            ],
        );

        // starting_datetime: 開始供電時間
        // tap_datetime: 插槍時間，插槍時間肯定在開始供電時間前面

        $param = $request->only(['order_id', 'serial_number', 'starting_datetime', 'tap_datetime']);
        Log::info('pile-starting:', ['data' => $param]);
        // return $this->success(new \stdClass());
        $order_id = $param['order_id'];
        $serial_number = $param['serial_number'];

        $pile_info = ChargingPile::query()->where('serial_number', $serial_number)->first();

        if ($pile_info) {
            $order_info = Order::query()->where('id', $order_id)->where('pile_id', $pile_info['id'])->first();
            if ($order_info && $order_info['charging_status'] == 0) {

                // 開始供電時間
                $power_supply_time = $param['starting_datetime'];

                // 插槍時間
                $tap_datetime = $param['tap_datetime'];

                // 開始供電時間 如果因爲回調延遲了，需要調換時間
                if ($order_info['power_supply_time'] && strtotime($order_info['power_supply_time']) < strtotime($power_supply_time)) {
                    $power_supply_time = $order_info['power_supply_time'];
                }

                // 插槍時間 如果因爲回調延遲了，需要調換時間
                if ($order_info['starting_time'] && strtotime($order_info['starting_time']) < strtotime($tap_datetime)) {
                    $tap_datetime = $order_info['starting_time'];
                }

                $r = Order::query()->where('id', $order_id)->update(
                    [
                        'starting_time' => $tap_datetime,
                        'power_supply_time' => $power_supply_time,
                    ]
                );

                if ($r) {
                    return $this->success(new \stdClass());
                }
            }
        }

        return $this->error();

    }


    /**
     * 結束充電
     * @param ChargingRequest $request
     * @return Response
     */
    public function ending(Request $request): Response
    {

        $request->validate(
            [
                'order_id' => ['required', 'integer'],
                'serial_number' => ['required'],
                'ending_datetime' => ['required', 'date'],
                'tap_datetime' => ['required', 'date'],
                'degree' => ['required'],
            ],
            [
                'order_id.required' => '充電記錄ID必須',
                'order_id.integer' => '充電記錄ID不正確',
                'ending_datetime.required' => 'ending_datetime 必須',
                'ending_datetime.date' => 'ending_datetime 不正確',
                'tap_datetime.required' => 'tap_datetime 必須',
                'tap_datetime.date' => 'tap_datetime 不正確',
                'degree.required' => 'degree 不正確',
            ],
        );

        $param = $request->only(['order_id', 'serial_number', 'ending_datetime', 'tap_datetime', 'degree']);
        Log::info('pile-ending:', ['data' => $param]);

        $order_id = $param['order_id'];
        $serial_number = $param['serial_number'];
        $m_degree = $param['degree'];
        $degree = round($m_degree/1000, 2);
        // return $this->success(new \stdClass());
        $pile_info = ChargingPile::query()->where('serial_number', $serial_number)->first();

        if ($pile_info) {
            $order_info = Order::query()->where('id', $order_id)->where('pile_id', $pile_info['id'])->first();

            if ($order_info && $order_info['status'] == 0) {
                DB::beginTransaction();

                try {

                    $ending_tap_datetime = $ending_time = $param['ending_datetime'];
                    $starting_time = $param['tap_datetime'];

                    if (!empty($order_info['starting_time']) && strtotime($order_info['starting_time']) < strtotime($starting_time)) {
                        $starting_time = $order_info['starting_time'];
                    }

                    // 開始供電時間 如果因爲回調延遲了，需要調換時間
                    if ($order_info['ending_time'] && strtotime($order_info['ending_time']) > strtotime($ending_time)) {
                        $ending_time = $order_info['ending_time'];
                    }

                    // 插槍時間 如果因爲回調延遲了，需要調換時間
                    if ($order_info['ending_tap_datetime'] && strtotime($order_info['ending_tap_datetime']) > strtotime($ending_tap_datetime)) {
                        $ending_tap_datetime = $order_info['ending_tap_datetime'];
                    }

                    // 計算充電時長
                    $seconds = strtotime($ending_time) - strtotime($starting_time);
                    $duration = $seconds > 0 ? ceil($seconds / 60) : 0;

                    // 充電合計收費，度數X充電時長  不足半小時，按半小時算
                    $total_charging = $degree > 0 ? round($degree * $order_info['charging']) : 0;

                    // 停車費
                    $parking_number = $duration > 0 ? ceil($duration / 30) : 0;
                    $total_parking_fee = $order_info['parking_fee'] * $parking_number;

                    // 充電優惠金額
                    $total_toll = $degree > 0 ? round($degree * $order_info['toll']) : 0;

                    // 優惠
                    $preferential = $order_info['preferential'];

                    // 充電總金額
                    $amount = $total_parking_fee + $total_charging - $total_toll - $preferential;
                    $amount = max($amount, 0);

                    $update_data = [
                        'ending_time' => $ending_time,
                        'ending_tap_datetime' => $ending_tap_datetime,
                        'charging_status' => 1,
                        'duration' => $duration,
                        'total_charging' => $total_charging,
                        'degree' => $degree,
                        'total_parking_fee' => $total_parking_fee,
                        'total_toll' => $total_toll,
                        'amount' => $amount,
                    ];

                    if (empty($order_info['starting_time'])) {
                        $update_data['starting_time'] = $starting_time;
                    }

                    if (empty($order_info['power_supply_time'])) {
                        $update_data['power_supply_time'] = $starting_time;
                    }

                    if ($amount <= 0) {
                        $update_data['status'] = 1;
                        $update_data['charging_status'] = 1;

                        OrderCard::query()->where('order_id', $order_info['id'])->delete();
                    }

                    Order::query()->where('id', $order_id)->update($update_data);

                    ChargingPile::query()->where('id', $pile_info['id'])->update(['status' => 0]);

                    DB::commit();

                    // 如果金額大於0，則進行支付
                    // ChargingJob::dispatch($info[], $order_id);

                    $clientIds = Redis::smembers('workerMan:userId:'. $order_info['user_id']);
                    if ($clientIds) {
                        // 計算充電時長
                        $seconds = strtotime($ending_time) - strtotime($order_info['starting_time']);
                        $duration = $seconds > 0 ? ceil($seconds / 60) : 0;

                        $client_data = [
                            'charging_status' => 1,
                            'data' => [
                                'parking_lot_name' => $order_info['parking_lot_name'],
                                'pile_no' => $order_info['pile_no'],
                                'starting_time' => $order_info['starting_time'],
                                'ending_time' => $order_info['ending_time'],
                                'duration' => $duration,
                                'total_charging' => $total_charging,
                                'degree' => $degree,
                                'total_parking_fee' => $total_parking_fee,
                                'total_toll' => $total_toll,
                                'amount' => $amount,
                                'preferential' => $preferential,
                                'id' => $order_id
                            ]
                        ];

                        foreach($clientIds as $clientId) {
                            Wrokerman::dispatch($clientId, $client_data);
                        }

                    }

                    // 扣款
                    if ($amount > 0) {
                        Payment::dispatch($order_id, $order_info['user_id'], $order_info['card_id']);
                    }

                    return $this->success();

                } catch (Throwable $e) {

                    DB::rollBack();
                    return $this->error($e->getMessage());
                }
            }

        }

        return $this->error();

    }

    /**
     * 開始充電
     * @param Request $request
     * @return Response
     */
    public function progress(Request $request): Response
    {

        $request->validate(
            [
                'order_id' => ['required', 'integer'],
                'serial_number' => ['required'],
                'starting_datetime' => ['required', 'date'],
                'progress_datetime' => ['required', 'date'],
                'tap_datetime' => ['required', 'date'],
                'degree' => ['required'],
            ],
            [
                'order_id.required' => '充電記錄ID必須',
                'order_id.integer' => '充電記錄ID不正確',
                'starting_datetime.required' => 'starting_datetime 必須',
                'starting_datetime.date' => 'starting_datetime 不正確',
                'progress_datetime.required' => 'progress_datetime 必須',
                'tap_datetime.required' => 'tap_datetime 必須',
                'progress_datetime.date' => 'progress_datetime 不正確',
                'tap_datetime.date' => 'tap_datetime 不正確',
                'degree.required' => 'degree 不正確',
            ],
        );

        $param = $request->only(['order_id', 'serial_number', 'starting_datetime', 'progress_datetime', 'degree', 'tap_datetime']);
        Log::info('pile-progress:', ['data' => $param]);

        $order_id = $param['order_id'];
        $serial_number = $param['serial_number'];
        $m_degree = $param['degree'];
        $degree = round($m_degree/1000, 2);
        // return $this->success(new \stdClass());
        $pile_info = ChargingPile::query()->where('serial_number', $serial_number)->first();

        if ($pile_info) {
            $order_info = Order::query()->where('id', $order_id)->where('pile_id', $pile_info['id'])->first();
            if ($order_info && $order_info['charging_status'] == 0) {

                // 當前匯報充電數據時間
                $ending_time = $param['progress_datetime'];

                // 開始供電時間
                $power_supply_time = $param['starting_datetime'];

                // 插槍時間
                $tap_datetime = $param['tap_datetime'];

                // 開始供電時間 如果因爲回調延遲了，需要調換時間
                if ($order_info['ending_time'] && strtotime($order_info['ending_time']) > strtotime($ending_time)) {
                    $ending_time = $order_info['ending_time'];
                }

                // 開始供電時間 如果因爲回調延遲了，需要調換時間
                if ($order_info['power_supply_time'] && strtotime($order_info['power_supply_time']) < strtotime($power_supply_time)) {
                    $power_supply_time = $order_info['power_supply_time'];
                }

                // 插槍時間 如果因爲回調延遲了，需要調換時間
                if ($order_info['starting_time'] && strtotime($order_info['starting_time']) < strtotime($tap_datetime)) {
                    $tap_datetime = $order_info['starting_time'];
                }

                $r = Order::query()->where('id', $order_id)->update(
                    [
                        'starting_time' => $tap_datetime,
                        'ending_time' => $ending_time,
                        'ending_tap_datetime' => $ending_time,
                        'power_supply_time' => $power_supply_time,
                        'degree' => $degree,
                    ]
                );

                if ($r) {
                    // $clientId = Redis::get('workerman-userId-' . $order_info['user_id']);
                    $clientIds = Redis::smembers('workerMan:userId:'. $order_info['user_id']);
                    if ($clientIds) {
                        // 計算充電時長
                        $seconds = strtotime($ending_time) - strtotime($tap_datetime);
                        $duration = $seconds > 0 ? ceil($seconds / 60) : 0;

                        $client_data = [
                            'charging_status' => $order_info['charging_status'],
                            'data' => [
                                'degree' => $degree,
                                'duration' => $duration
                            ]
                        ];
                        // Wrokerman::dispatch($clientId, $client_data);
                        foreach($clientIds as $clientId) {
                            Wrokerman::dispatch($clientId, $client_data);
                        }
                    }

                    return $this->success(new \stdClass());
                }
            }
        }

        return $this->error();

    }

    /**
     * 開始充電
     * @param Request $request
     * @return Response
     */
    public function reporting(Request $request): Response
    {

        $request->validate(
            [
                'serial_number' => ['required'],
                'data' => ['required'],
            ],
            [
                'serial_number.required' => 'serial_number 必須',
                'data.required' => 'data 必須',
            ],
        );

        $param = $request->only(['serial_number', 'data']);
        Log::info('pile-reporting:', ['data' => $param]);
        // return $this->success(new \stdClass());
        $serial_number = $param['serial_number'];
        $pile_info = ChargingPile::query()->where('serial_number', $serial_number)->first();

        if ($pile_info) {
            PileError::query()->create([
                'pile_id' => $pile_info['id'],
                'serial_number' => $serial_number,
                'data' => is_array($param['data']) ? json_encode($param['data']) : $param['data']
            ]);
        }

        return $this->success(new \stdClass());

    }


}
