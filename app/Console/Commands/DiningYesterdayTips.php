<?php

namespace App\Console\Commands;

use App\Jobs\RegularPushJob;
use App\Models\Common\Banner;
use App\Models\Common\DiningBooking;
use App\Models\Common\DiningHotel;
use App\Models\Frontend\User\User;
use App\Models\Order\Order;
use App\Services\Common\InvoiceService;
use App\Services\Common\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DiningYesterdayTips extends Command
{
    /**
     * 餐旅前一天提醒是否取消通知
     *
     * @var string
     */
    protected $signature = 'DiningYesterdayTips';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '餐旅前一天提醒是否取消通知';

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

        Log::info('餐旅前一天提醒是否取消通知-開始');

        // 把前一天扣款失敗的找出來
        // $start_yesterday = date('Y-m-d 00:00:00', strtotime('-1 day'));
        // $end_yesterday = date('Y-m-d 23:59:59', strtotime('-1 day'));
        $start_yesterday = date('Y-m-d 00:00:00', strtotime('+1 day'));
        $end_yesterday = date('Y-m-d 23:59:59', strtotime('+1 day'));
        DiningBooking::query()->where('status', 0)->whereIn('payment_status', [0])
            ->where('booking_datetime', '>=', $start_yesterday)
            ->where('booking_datetime', '<=', $end_yesterday)
            ->chunkById(200, function ($order) {

                $hotel_array = [];
                foreach($order as $v) {

                    try {
                        $key = 'dining_cancel_yesterday_tips';

                        if (!isset($hotel_array[$v->dining_hotel_id])) {
                            $hotel_array[$v->dining_hotel_id] = DiningHotel::query()->select('name')->where('id', $v->dining_hotel_id)->first();
                        }

                        $replace = [
                            'shop_name' => $hotel_array[$v->dining_hotel_id]['name'],
                            'booking_datetime' => $v->booking_datetime,
                        ];
                        RegularPushJob::dispatch($v->user_id, $key, $replace);

                    } catch (\Throwable $e) {
                        Log::info('餐旅前一天提醒是否取消通知error: ' . $e->getMessage());
                    }

                }
        });

        Log::info('餐旅前一天提醒是否取消通知-結束');

    }
}
