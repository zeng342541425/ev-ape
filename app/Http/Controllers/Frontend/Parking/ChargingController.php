<?php

namespace App\Http\Controllers\Frontend\Parking;

use App\Http\Controllers\Frontend\BaseController;
use App\Http\Requests\Frontend\Parking\Charging\ChargingRequest;
use App\Http\Requests\Frontend\Parking\Charging\PileRequest;
use App\Http\Requests\Frontend\Parking\Charging\ScoreRequest;
use App\Models\Common\Appointment;
use App\Models\Common\InvoiceDonation;
use App\Models\Common\OrderCard;
use App\Models\Frontend\User\User;
use App\Models\Order\Order;
use App\Models\Parking\Brand;
use App\Models\Parking\ChargingPile;
use App\Models\Parking\ParkingLot;
use App\Models\User\CreditCard;
use App\Models\User\Invoice;
use App\Services\Common\Common;
use App\Services\Common\PileService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;


class ChargingController extends BaseController
{

    public function preDevice(Request $request): Response
    {

        $user = $request->user();

        $no = $request->get('no');
        $info = ChargingPile::query()->where('no', $no)->first();
        if (!$info) {
            return $this->error('充電樁編號不正確');
        }

        $res = (new PileService())->pileStatus($info->serial_number);
        if (!$res) {
            return $this->error('充電槍沒有置入');
        }

        return $this->success();

    }

    // 掃碼前檢測
    public function preDetection(Request $request): Response
    {

        $user = $request->user();

        // 先查詢充電中的
        $order_info = Order::query()->where('user_id', $user['id'])->where('charging_status', 0)->first();
        if ($order_info) {
            $res = [
                'degree' => $order_info['degree'],
                'duration' => $order_info['duration'],
            ];
            return $this->success([
                'status' => 0,
                'info' => $res
            ]);
        }

        // 如果有未支付的充電記錄, 提示語請和middle precondition 一起改掉
        $order_info = Order::query()->select('id')->where('user_id', $user['id'])->whereNotIn('status', [1, 2])->first();
        if ($order_info) {
            $msg = '您正在充電或者有充電記錄未支付，功能不能使用';
            return $this->error($msg);
        }

        return $this->success([
            'status' => 1,
            'info' => new \stdClass()
        ]);
    }

    /**
     * 掃碼或者輸入充電樁編號
     *
     * 充電樁狀態；0:空閑（可以充電）；1：設備充電中；2：設備故障；9：該設備已被預約，10：充電樁已經關閉
     *
     * @param PileRequest $request
     * @return Response
     */
    public function pile(PileRequest $request): Response
    {

        $no = $request->get('no');

        $info = ChargingPile::query()->where('no', $no)->first();
        if (!$info) {
            return $this->error('充電樁編號不正確');
        }

        $res = (new PileService())->pileStatus($info->serial_number);
        if (!$res) {
            return $this->error('設備目前無法使用');
        }

        $status = $info['status'];

        if ($info['stat'] == 0) {
            $status = 10;
        } else {
            if ($status == 0) {
                $ex = Appointment::query()
                    ->where('pile_id', $info['id'])
                    ->where('expired_at', '>=', date('Y-m-d H:i:s'))
                    ->where('appointment_at', '<=', date('Y-m-d H:i:s'))
                    ->whereIn('status', [0])
                    ->exists();
                if ($ex) {
                    $status = 9;
                }
            }
        }


        // if ($info['status'] == 1) {
        //     // todo 提示信息
        //     return $this->error('設備使用中');
        // }
        //
        // if ($info['status'] == 2) {
        //     // todo 提示信息
        //     return $this->error('設備故障');
        // }

        $parking_info = ParkingLot::query()->where('id', $info['parking_lot_id'])->first();
        if (!$parking_info || $parking_info['status'] == 0) {
            return $this->error('設備暫停使用');
        }

        return $this->success([
            'parking_lot_name' => $parking_info['parking_lot_name'],
            'parking_fee' => $parking_info['parking_fee'],
            'no' => $info['no'],
            'charging' => $info['charging'],
            'status' => $status,
        ]);

    }

    /**
     * 開始充電
     * @param ChargingRequest $request
     * @return Response
     */
    public function starting(Request $request): Response
    {

        $no = $request->get('no');
        $card_id = $request->get('card_id');
        $invoice_type = $request->get('invoice_type');
        $invoice_id = $request->get('invoice_id');
        $brand_id = $request->get('brand_id');

        $user = $request->user();

        if (empty($user['email'])) {
            return $this->error('請設定信箱');
        }

        $info = ChargingPile::query()->where('no', $no)->first();
        if (!$info) {
            return $this->error('充電樁編號不正確');
        }

        if ($info['status'] == 1) {
            // todo 提示信息
            return $this->error('設備使用中');
        }

        if ($info['status'] == 2) {
            // todo 提示信息
            return $this->error('設備故障');
        }

        // todo 請求充電樁的接口，確認狀態并且獲取時間
        $starting_time = date('Y-m-d H:i:s');

        $parking_info = ParkingLot::query()->where('id', $info['parking_lot_id'])->first();
        if (!$parking_info || $parking_info['status'] == 0) {
            return $this->error('設備暫停使用');
        }

        $card_info = CreditCard::query()->where('id', $card_id)->where('user_id', $user['id'])->first();
        if (!$card_info) {
            return $this->error('信用卡不正確');
        }

        if ($invoice_type != 4) {
            $invoice_info = Invoice::query()->select('title', 'tax_id')->where('id', $invoice_id)->where('user_id', $user['id'])->first();

        } else {
            $invoice_info = InvoiceDonation::query()->select('institution as title', 'id_card as tax_id')->where('id', $invoice_id)->first();

        }
        if (!$invoice_info) {
            return $this->error('發票不正確');
        }

        $invoice_info_str = json_encode([
            'invoice_info' => $invoice_info,
        ]);

        $brand_info = Brand::query()->where('id', $brand_id)->first();
        if (!$brand_info) {
            return $this->error('車用品牌不正確');
        }

        $res = (new PileService())->pileStatus($info->serial_number);
        if (!$res) {
            return $this->error('充電槍沒有置入');
        }

        DB::beginTransaction();

        try {
            $current_date = date('Y-m-d H:i:s');
            $order_id = Order::query()->insertGetId([
                'order_number' => Common::generateNo(),
                'user_id' => $user['id'],
                'username' => $user['name'],
                'phone' => $user['phone'],
                'pile_id' => $info['id'],
                'pile_no' => $no,
                'parking_lot_id' => $info['parking_lot_id'],
                'parking_lot_name' => $parking_info['parking_lot_name'],
                'trade_date' => date('Y-m-d'),
                // 'starting_time' => $starting_time,
                'card_id' => $card_id,
                'card_number' => $card_info['card_number'],
                'parking_fee' => $parking_info['parking_fee'],
                'toll' => $info['toll'],
                'charging' => $info['charging'],
                'brand_id' => $brand_id,
                'brand_name' => $brand_info['brand_name'],
                'preferential' => $info['preferential'],
                // 'charging_unit' => $info['charging_unit'],
                'invoice_type' => $invoice_type,
                'invoice_id' => $invoice_id,
                'invoice_info' => $invoice_info_str,
                'created_at' => $current_date,
                'updated_at' => $current_date,
            ]);
            if (!$order_id) {
                throw new Exception('設備連接失敗');
            }

            $order_card = [
                'order_id' => $order_id,
                'card_key' => $card_info['card_key'],
                'card_token' => $card_info['card_token'],
                'currency' => $card_info['currency'],
            ];
            OrderCard::query()->create($order_card);

            // 預約已抵達
            $appointment = Appointment::query()->select('id')
                ->where('user_id', $user['id'])
                ->where('pile_id', $info['id'])
                ->where('expired_at', '<=', $current_date)
                ->where('status', 0)
                ->first();
            if ($appointment) {
                Appointment::query()->where('id', $appointment['id'])->update([
                    'status' => 1
                ]);
            }

            $r = ChargingPile::query()->where('no', $no)->update(['status' => 1]);
            if (!$r) {
                throw new Exception('設備連接失敗');
            }

            $code = (new PileService())->charging($info->serial_number, $order_id);
            if (!$code) {
                // 成功
                throw new Exception('start-charging請求失敗，'.$order_id);
            }

            // 更新車用品牌
           // User::query()->where('id', $user['id'])->update(['brand_id' => $brand_id]);

            DB::commit();

            // ChargingJob::dispatch($info[], $order_id);
            return $this->success([
                'degree' => 0.00,
                'duration' => 0,
                'amount' => 0,
                'order_id' => $order_id,
                'no' => $no
            ]);

        } catch (Throwable $e) {

            DB::rollBack();
            return $this->error($e->getMessage());
        }

    }


    // /**
    //  * 開始充電
    //  * @param ChargingRequest $request
    //  * @return Response
    //  */
    // public function ending(Request $request): Response
    // {
    //
    //     $param = $request->all();
    //     Log::info('ending:', ['data' => $param]);
    //     return $this->success(new \stdClass());
    //
    //     $user = $request->user();
    //
    //     $order_info = Order::query()->where('user_id', $user['id'])->where('status', 0)->orderBy('id', 'desc')->first();
    //     if (!$order_info) {
    //         return $this->error('充電結束');
    //     }
    //
    //     $no = $order_info['pile_no'];
    //     $pile_id = $order_info['pile_id'];
    //     $card_id = $order_info['card_id'];
    //     $parking_lot_id = $order_info['parking_lot_id'];
    //
    //     $parking_info = ParkingLot::query()->where('id', $parking_lot_id)->first();
    //
    //     // todo 請求充電樁，獲取度數并且結束供電
    //     $degree = 10.08;
    //
    //     $starting_timestamp = strtotime($order_info['starting_time']);
    //     $ending_timestamp = time();
    //     $ending_time = date('Y-m-d H:i:s');
    //     $duration = round(($ending_timestamp - $starting_timestamp) / 60);
    //     $amount = Common::getAmount($duration, $order_info['charging']);
    //
    //     DB::beginTransaction();
    //
    //     try {
    //
    //         $total_parking_fee = $order_info['parking_fee'] * ceil($duration/60);
    //         $total_toll = $order_info['toll'] * ceil($duration/60);
    //         $order_id = Order::query()->where('id', $order_info['id'])->update([
    //             'ending_time' => $ending_time,
    //             'degree' => $degree,
    //             'duration' => $duration,
    //             'total_parking_fee' => $total_parking_fee,
    //             'total_toll' => $total_toll,
    //             'amount' => $amount,
    //             'status' => 1,
    //         ]);
    //         if (!$order_id) {
    //             throw new Exception('設備連接失敗');
    //         }
    //
    //         $r = ChargingPile::query()->where('no', $no)->update(['status' => 0]);
    //         if (!$r) {
    //             throw new Exception('設備連接失敗');
    //         }
    //
    //         DB::commit();
    //
    //         // 扣款
    //         Payment::dispatch($order_info['id'], $order_info['user_id'], $order_info['card_id']);
    //
    //         return $this->success([
    //             'degree' => $degree,
    //             'duration' => $duration,
    //             'amount' => $amount,
    //             'starting_time' => $order_info['starting_time'],
    //             'ending_time' => $ending_time,
    //             'parking_lot_name' => $parking_info['parking_lot_name'],
    //             'total_parking_fee' => $total_parking_fee,
    //             'total_toll' => $total_toll,
    //             'charging' => $parking_info['parking_fee'],
    //             'no' => $no
    //         ]);
    //
    //     } catch (Throwable $e) {
    //
    //         DB::rollBack();
    //         return $this->error($e->getMessage());
    //     }
    //
    // }
    //
    // /**
    //  * 開始充電
    //  * @param Request $request
    //  * @return Response
    //  */
    // public function progress(Request $request): Response
    // {
    //
    //     $param = $request->all();
    //     Log::info('progress:', ['data' => $param]);
    //     return $this->success(new \stdClass());
    //
    // }
    //
    // /**
    //  * 開始充電
    //  * @param Request $request
    //  * @return Response
    //  */
    // public function reporting(Request $request): Response
    // {
    //
    //     $param = $request->all();
    //     Log::info('reporting:', ['data' => $param]);
    //     return $this->success(new \stdClass());
    //
    // }

    /**
     * 評分
     *
     * @param ScoreRequest $request
     * @return Response
     */
    public function score(ScoreRequest $request): Response
    {

        $id = $request->get('order_id');
        $star = $request->get('star');

        $user = $request->user();

        $item = Order::query()->select('id')->where('id', $id)->whereIn('charging_status', [1])
            ->where('user_id', $user['id'])->first();
        if (!$item || $item['star'] != 0) {
            return $this->success();
        }

        Order::query()->where('id', $id)->update([
            'star' => $star
        ]);

        return $this->success();

    }


}
