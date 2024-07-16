<?php

namespace App\Services\Common;

use App\Models\Common\DiningBooking;
use App\Models\Common\DiningBookingCard;
use App\Models\Common\OrderCard;
use App\Models\Order\Order;
use App\Models\Order\OrderPaymentRequest;
use App\Models\User\CreditCard;
use Illuminate\Support\Facades\Log;


class PaymentService
{

    public function pay($order_info, $user_id = 0, $card_id = 0): bool
    {

        // 最愛篩選
        if ($order_info) {

            if ($order_info['amount'] <= 0) {
                return true;
            }

            // $card_id = $card_id == 0 ? $order_info['card_id'] : $card_id;
            // $card_info = CreditCard::query()->where('id', $card_id)->where('user_id', $user_id)->first();

            $card_info = OrderCard::query()->where('order_id', $order_info['id'])->first();
            $request_data = [
                'card_key' => $card_info['card_key'],
                'card_token' => $card_info['card_token'],
                'currency' => $card_info['currency'],
                'amount' => $order_info['amount'],
                'order_number' => $order_info['order_number'],
                // 'bank_transaction_id' => $order_info['order_number'],
                'details' => $order_info['trade_date'] . ' ' . $order_info['starting_time'] . '於' . $order_info['parking_lot_name'] . '充電' . $order_info['duration'] . '分鐘',
                'result_url' => [
                    'backend_notify_url' => route('backend_notify_url')
                ]
            ];

            $tapPayService = new TapPayService();
            $res = $tapPayService->pay($request_data);

            Log::info('pay:res', ['data' => $res ?: []]);
            if ($res) {
                unset($request_data['card_key']);
                unset($request_data['card_token']);
                unset($request_data['currency']);
                OrderPaymentRequest::query()->create([
                    'user_id' => $user_id,
                    'card_id' => $order_info['card_id'],
                    'order_number' => $order_info['order_number'],
                    'api' => $tapPayService->getPaymentByTokenApi(),
                    'rec_trade_id' => $res['rec_trade_id'] ?? '',
                    'auth_code' => $res['auth_code'] ?? '',
                    'content' => json_encode($res),
                    'requests_data' => json_encode($request_data),
                    'type' => 1
                ]);

                Order::query()->where('order_number', $order_info['order_number'])->update([
                    'rec_trade_id' => $res['rec_trade_id'],
                    'status' => 2,
                    'payment_time' => date('Y-m-d H:i:s'),
                ]);

                OrderCard::query()->where('order_id', $order_info['id'])->delete();

                return true;

            }

        }

        return false;

    }

    public function payDining($order_id = 0, $user_id = 0, $backend_notify_url = 'payment_dining_notify'): bool
    {
        $order_info = DiningBooking::query()->with('dining_hotel:id,name')->where('id', $order_id)->where('user_id', $user_id)->first();

        // 最愛篩選
        if ($order_info) {

            // $card_id = $card_id == 0 ? $order_info['card_id'] : $card_id;
            // $card_info = CreditCard::query()->where('id', $card_id)->where('user_id', $user_id)->first();

            $card_info = DiningBookingCard::query()->where('order_id', $order_id)->first();
            $request_data = [
                'card_key' => $card_info['card_key'] ?? '',
                'card_token' => $card_info['card_token'] ?? '',
                'currency' => $card_info['currency'] ?? '',
                'amount' => $order_info['number'] * $order_info['charging'],
                'order_number' => $order_info['order_number'],
                // 'bank_transaction_id' => $order_info['order_number'],
                'details' => $order_info['dining_hotel']['name'] . ' ' . $order_info['number'] . '人現場報到',
                'result_url' => [
                    'backend_notify_url' => route($backend_notify_url)
                ]
            ];

            $tapPayService = new TapPayService();
            $res = $tapPayService->pay($request_data);

            if ($res) {
                unset($request_data['card_key']);
                unset($request_data['card_token']);
                unset($request_data['currency']);
                OrderPaymentRequest::query()->create([
                    'user_id' => $user_id,
                    'card_id' => $order_info['card_id'],
                    'order_number' => $order_info['order_number'],
                    'api' => $tapPayService->getPaymentByTokenApi(),
                    'rec_trade_id' => $res['rec_trade_id'] ?? '',
                    'auth_code' => $res['auth_code'] ?? '',
                    'content' => json_encode($res),
                    'requests_data' => json_encode($request_data),
                    'type' => 2
                ]);

                DiningBooking::query()->where('id', $order_info['id'])->update([
                    'rec_trade_id' => $res['rec_trade_id'],
                    'payment_time' => date('Y-m-d H:i:s'),
                ]);

                DiningBookingCard::query()->where('order_id', $order_id)->delete();

                return true;

            }

        }

        return false;

    }
}

