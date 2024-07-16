<?php

namespace App\Console\Commands;

use App\Jobs\RegularPushJob;
use App\Models\Common\Banner;
use App\Models\Common\DiningBooking;
use App\Models\Frontend\User\User;
use App\Models\Order\Order;
use App\Services\Common\InvoiceService;
use App\Services\Common\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResetPayment extends Command
{
    /**
     * 充電記錄中扣款失敗，重新向TAP PAY發起扣款請求
     *
     * @var string
     */
    protected $signature = 'ResetPayment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '充電扣款失敗重新發起扣款';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {

        Log::info('充電扣款失敗重新發起扣款-開始');

        // 把前一天扣款失敗的找出來
        $start_yesterday = date('Y-m-d 00:00:00', strtotime('-1 day'));
        $end_yesterday = date('Y-m-d 23:59:59', strtotime('-1 day'));
        $pay_service = new PaymentService();
        Order::query()->where('charging_status', 1)->whereIn('status', [0, 3])
            ->where('updated_at', '>=', $start_yesterday)
            ->where('updated_at', '<=', $end_yesterday)
            ->chunkById(200, function ($order) use ( $pay_service) {

            foreach($order as $v) {

                if ($pay_service->pay($v, $v->user_id)) {
                    // $r = Order::query()->where('id', $v->id)->update([
                    //     'status' => 2,
                    // ]);

                    $key = 'order_payment_supplement_success';

                    // 開發票
                    $user = User::query()->where('id', $v->user_id)->first();
                    $res = (new InvoiceService())->sendOrder($v, $user);
                    if ($res) {
                        !empty($res['invoice_number']) && Order::query()->where('id', $v->id)->update([
                            'invoice_number' => $res['invoice_number'],
                        ]);
                    }
                } else {

                    $key = 'order_payment_supplement';

                }

                $replace = [
                    'ending_datetime' => $v->ending_time,
                    'amount' => $v->amount,
                    'order_no' => $v->order_number,
                ];
                RegularPushJob::dispatch($v->user_id, $key, $replace);

            }
        });

        Log::info('充電扣款失敗重新發起扣款-結束');

    }
}
