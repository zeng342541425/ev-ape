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

class ResetDiningPayment extends Command
{
    /**
     * 餐旅記錄中扣款失敗，重新向TAP PAY發起扣款請求
     *
     * @var string
     */
    protected $signature = 'ResetDiningPayment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '餐旅扣款失敗重新發起扣款';

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

        Log::info('餐旅扣款失敗重新發起扣款-開始');

        // 把前一天扣款失敗的找出來
        $start_yesterday = date('Y-m-d 00:00:00', strtotime('-1 day'));
        $end_yesterday = date('Y-m-d 23:59:59', strtotime('-1 day'));
        $pay_service = new PaymentService();
        DiningBooking::query()->whereIn('payment_status', [3])
            ->where('updated_at', '>=', $start_yesterday)
            ->where('updated_at', '<=', $end_yesterday)
            ->chunkById(200, function ($order) use ( $pay_service) {

            foreach($order as $v) {

                try {

                    if ($pay_service->payDining($v->id, $v->user_id)) {

                        $r = DiningBooking::query()->where('id', $v->id)->update([
                            'payment_status' => 1,
                        ]);

                        $key = 'dining_payment_supplement_success';

                        // RegularPushJob::dispatch($v->user_id, $key, $replace);

                        // 開發票
                        $user = User::query()->where('id', $v->user_id)->first();
                        $res = (new InvoiceService())->send($v, $user);
                        if ($res) {
                            !empty($res['invoice_number']) && DiningBooking::query()->where('id', $v->id)->update([
                                'invoice_number' => $res['invoice_number'],
                            ]);
                        }

                    } else {
                        // $r = DiningBooking::query()->where('id', $v->id)->update([
                        //     'payment_status' => 3,
                        // ]);

                        $key = 'dining_payment_supplement';

                    }

                    $replace = [
                        'datetime' => $v->booking_datetime,
                        'amount' => $v->number * $v->charging,
                        'order_no' => $v->order_number
                    ];
                    RegularPushJob::dispatch($v->user_id, $key, $replace);

                } catch (\Throwable $e) {
                    Log::info('餐旅扣款失敗重新發起扣款error: ' . $e->getMessage());
                }

            }
        });

        Log::info('餐旅扣款失敗重新發起扣款-結束');

    }
}
