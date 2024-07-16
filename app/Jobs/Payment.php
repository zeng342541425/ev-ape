<?php

namespace App\Jobs;

use App\Mail\Notice;
use App\Models\Frontend\User\User;
use App\Models\Order\Order;
use App\Services\Common\InvoiceService;
use App\Services\Common\PaymentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class Payment extends BaseJob
{

    public int $tries = 3;

    private int $order_id;
    private int $user_id;
    private int $card_id;

    public string $name = "支付";
    public string $desc = "充电完成后异步调用tappay支付";

    public function __construct(int $order_id = 0, int $user_id = 0, int $card_id = 0)
    {

        parent::__construct();

        $this->onQueue('order_payment');

        $this->order_id = $order_id;
        $this->user_id = $user_id;
        $this->card_id = $card_id;
    }

    public function handle()
    {

        $order_info = Order::query()->where('id', $this->order_id)->where('user_id', $this->user_id)->first();
        $res = (new PaymentService())->pay($order_info, $this->user_id);
        $user = User::query()->where('id', $this->user_id)->first();
        $update = [];
        if ($res) {
            // 開發票

            // $user = User::query()->where('id', $this->user_id)->first();
            $res = (new InvoiceService())->sendOrder($order_info, $user);
            if ($res && !empty($res['invoice_number'])) {
                $update = [
                    'invoice_number' => $res['invoice_number'],
                ];
            }

            // 推播
            $key = 'order_payment_success';

            Log::info('請求支付api成功，order_id' . $this->order_id);
        } else {
            $update = [
                'status' => 3
            ];
            Log::info('請求支付api失敗，order_id' . $this->order_id);
            // throw new \Exception('成功了', 405);

            // 推播
            $key = 'order_payment_error';

        }

        !empty($update) && Order::query()->where('id', $this->order_id)->update($update);

        $replace = [
            'ending_datetime' => $order_info['ending_time'],
            'amount' => $order_info['amount'],
            'order_no' => $order_info['order_number'],
        ];

        RegularPushJob::dispatch($this->user_id, $key, $replace);

        // 充電刷卡失敗
        if (!$res) {
            $data = [
                'username' => $order_info['username'],
                'ending_time' => $order_info['ending_time'],
                'amount' => $order_info['amount'],
                'order_no' => $order_info['order_number'],
            ];
          //  EmailJob::dispatch($user['email'], $data, 4);
            Mail::to($user['email'])->send(new Notice("order_payment_error", $data));
        }else{
            $data = [
                'username' => $order_info['username'],
                'ending_time' => $order_info['ending_time'],
                'amount' => $order_info['amount'],
                'order_no' => $order_info['order_number'],
                'pile_no' => $order_info['pile_no'],
                'degree' => $order_info['degree'],
                'duration' => (integer)$order_info['duration']/60,
            ];
            Mail::to($user['email'])->send(new Notice("order_payment_success", $data));
        }

    }
}
