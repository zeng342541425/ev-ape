<?php

namespace App\Http\Controllers\Frontend\Order;

use App\Http\Controllers\Frontend\BaseController;
use App\Http\Requests\Frontend\Parking\Map\FavoriteRequest;
use App\Http\Requests\Frontend\Parking\Map\MapRequest;
use App\Jobs\RegularPushJob;
use App\Models\Common\DiningBooking;
use App\Models\Frontend\User\User;
use App\Models\Order\InvoiceRequest;
use App\Models\Order\Order;
use App\Models\Order\OrderNotifyRequest;
use App\Models\Order\OrderPaymentRequest;
use App\Models\Parking\ChargingPile;
use App\Models\Parking\Favorite;
use App\Models\Parking\ParkingLot;
use App\Models\User\CardRequests;
use App\Models\User\CreditCard;
use App\Services\Common\InvoiceService;
use App\Services\Common\PaymentService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Services\Common\TapPayService;
use Illuminate\Http\Request;


class PaymentController extends BaseController
{

    /**
     * 支付
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {

        $order_id = $request->get('order_id', 0);
        $card_id = $request->get('card_id', 0);

        $user = $request->user();
        $user_id = $user['id'];

        $order_info = Order::query()->where('id', $order_id)->where('user_id', $user_id)->first();
        $res = (new PaymentService())->pay($order_info, $user_id, $card_id);

        return $res ? $this->success() : $this->error();

    }

    // 充電金流notify
    public function notify(Request $request)
    {
        $data = $request->all();

        Log::info('notify: ', ['data' => $data]);

        if (!isset($data['order_number']) || !isset($data['rec_trade_id'])) {
            return;
        }

        OrderNotifyRequest::query()->create([
            'order_number' => $data['order_number'],
            'rec_trade_id' => $data['rec_trade_id'],
            'content' => json_encode($data),
        ]);

        $order_info = Order::query()->where('order_number', $data['order_number'])->first();

        if ($order_info['status'] != 2) {
            $tapPayService = new TapPayService();
            $request_data['rec_trade_id'] = $data['rec_trade_id'];
            $res = $tapPayService->history($request_data);
            if ($res) {

                $trade_info = $res['trade_history'];
                if ($trade_info['action'] == 5 || $trade_info['action'] == 4) {
                    // 交易狀態
                    // 0 授權
                    // 1 請款
                    // 3 退款
                    // 4 待付款
                    // 5 取消
                    // 6 取消退款
                    return;
                }

                if ($trade_info['success']) {
                    $r = Order::query()->where('id', $order_info['id'])->update([
                        'status' => 2,
                        'rec_trade_id' => $data['rec_trade_id'],
                        'payment_time' => date('Y-m-d H:i:s'),
                    ]);
                    if (!$r) {
                        Log::info('notify 修改訂單狀態失敗, order_number:' . $data['order_number']);
                    }

                    // 發票
                    $order = Order::query()->where('id', $order_info['id'])->first();
                    $user = User::query()->where('id', $order['user_id'])->first();
                    $res = (new InvoiceService())->sendOrder($order, $user);
                    if ($res) {
                        !empty($res['invoice_number']) && Order::query()->where('id', $order_info['id'])->update([
                            'invoice_number' => $res['invoice_number'],
                        ]);
                    }

                }

            }
        }

    }

    public function notifyBind(Request $request)
    {
        $data = $request->all();
        Log::info('notify_bind回調 ', ['data' => $data]);

        if (!isset($data['status']) || $data['status'] != 0) {
            return;
        }

        if (empty($data['rec_trade_id'])) {
            return;
        }

        $CardRequests = CardRequests::query()->where('rec_trade_id', $data['rec_trade_id'])->orderByDesc('id')->first();

        if (!$CardRequests) {
            return;
        }
        $res = json_decode($CardRequests->content, true);
        $requests_data = json_decode($CardRequests->requests_data, true);

        if (CreditCard::query()->where('user_id', $CardRequests->user_id)->where('card_identifier', $res['card_identifier'])->exists()) {
            return $this->success();
        }


        $current_date = date('Y-m-d H:i:s');
        $card_number = $res['card_info']['bin_code'] . str_repeat('*', 6) . $res['card_info']['last_four'];
        $card_id = CreditCard::query()->insertGetId([
            'user_id' => $CardRequests->user_id,
            'card_number' => $card_number,
            'card_key' => $res['card_secret']['card_key'],
            'card_token' => $res['card_secret']['card_token'],
            'currency' => $requests_data['currency'],
            'funding' => $res['card_info']['funding'],
            'type' => $res['card_info']['type'],
            'card_identifier' => $res['card_identifier'],
            'created_at' => $current_date,
            'updated_at' => $current_date,
        ]);

        if (!$card_id) {
            return;
        }
        $CardRequests->update(['card_id' => $card_id]);
        // 推播
        $key = 'card_bind_success';
        RegularPushJob::dispatch($CardRequests->user_id, $key);

        return $this->success();


    }

    public function redirectBind(Request $request)
    {
        $data = $request->all();

        Log::info('redirect_bind: ', ['data' => $data]);
        return "0k";

    }


    // 餐旅現場報道
    public function diningDealNotify(Request $request)
    {
        $data = $request->all();

        Log::info('diningDealNotify: ', ['data' => $data]);
        $data['status'] = 1;

        $this->diningNotify($data);
    }

    // 餐旅沒有去，金流
    public function diningHasNotLeftNotify(Request $request)
    {
        $data = $request->all();

        Log::info('diningHasNotLeftNotify: ', ['data' => $data]);
        $data['status'] = 3;

        $this->diningNotify($data);
    }

    /**
     * 餐旅支付成功回調
     *
     * @param $data
     * @return void
     */
    protected function diningNotify($data): void
    {

        if (!isset($data['order_number']) || !isset($data['rec_trade_id'])) {
            return;
        }

        OrderNotifyRequest::query()->create([
            'order_number' => $data['order_number'],
            'rec_trade_id' => $data['rec_trade_id'],
            'content' => json_encode($data),
            'type' => 2
        ]);

        $order_info = DiningBooking::query()->where('order_number', $data['order_number'])->first();

        if ($order_info['payment_status'] != 1) {
            $tapPayService = new TapPayService();
            $request_data['rec_trade_id'] = $data['rec_trade_id'];
            $res = $tapPayService->history($request_data);
            if ($res) {

                $trade_info = $res['trade_history'];
                if ($trade_info['action'] == 5 || $trade_info['action'] == 4) {
                    // 交易狀態
                    // 0 授權
                    // 1 請款
                    // 3 退款
                    // 4 待付款
                    // 5 取消
                    // 6 取消退款
                    return;
                }

                if ($trade_info['success']) {
                    $r = DiningBooking::query()->where('id', $order_info['id'])->update([
                        'payment_status' => 1,
                        'rec_trade_id' => $data['rec_trade_id'],
                        'status' => $data['status'],
                        'payment_time' => date('Y-m-d H:i:s'),
                    ]);
                    if (!$r) {
                        Log::info('diningNotify 修改訂單狀態失敗, order_number:' . $data['order_number']);
                    }

                    // 開發票
                    $order = DiningBooking::query()->where('id', $order_info['id'])->first();
                    $user = User::query()->where('id', $order['user_id'])->first();
                    $res = (new InvoiceService())->send($order, $user);
                    if ($res) {
                        !empty($res['invoice_number']) && DiningBooking::query()->where('id', $order['id'])->update([
                            'invoice_number' => $res['invoice_number'],
                        ]);
                    }

                }

            }
        }

    }

    public function invoiceNotify(Request $request): void
    {
        $data = $request->all();

        Log::info('invoiceNotify: ', ['data' => $data]);

        if (!isset($data['order_number']) || !isset($data['rec_invoice_id'])) {
            return;
        }

        $ex = InvoiceRequest::query()->where('order_number', $data['order_number'])->first();
        if ($ex) {
            InvoiceRequest::query()->where('id', $ex['id'])->update([
                'order_number' => $data['order_number'],
                'rec_trade_id' => $data['rec_trade_id'],
                'content' => json_encode($data),
                'yeah' => 1
            ]);
        } else {
            InvoiceRequest::query()->create([
                'order_number' => $data['order_number'],
                'rec_trade_id' => $data['rec_trade_id'],
                'content' => json_encode($data),
            ]);
        }


    }


}
