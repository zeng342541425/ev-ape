<?php

namespace App\Console\Commands;

use App\Jobs\RegularPushJob;
use App\Mail\Notice;
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
use Illuminate\Support\Facades\Mail;

class DiningFreeTips extends Command
{
    /**
     * 餐旅可免費取消天數的前一天提醒
     *
     * @var string
     */
    protected $signature = 'DiningFreeTips';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '餐旅可免費取消天數的前一天提醒';

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

        Log::info('餐旅可免費取消天數的前一天提醒-開始');

        // 把前一天扣款失敗的找出來
        $start_yesterday = date('Y-m-d 00:00:00');
        $end_yesterday = date('Y-m-d 23:59:59');
        DiningBooking::query()->where('status', 0)->whereIn('payment_status', [0])
            ->where('cancel_expired_at', '>=', $start_yesterday)
            ->where('cancel_expired_at', '<=', $end_yesterday)
            ->chunkById(200, function ($order) {

                $hotel_array = [];
                foreach($order as $v) {
                    $user = User::query()->find($v->user_id);
                    if (!$user) continue;
                    // $uid_array[] = $v->user_id;

                    if (!isset($hotel_array[$v->dining_hotel_id])) {
                        $hotel_array[$v->dining_hotel_id] = DiningHotel::query()->select('name')->where('id', $v->dining_hotel_id)->first();
                    }

                    $key = 'dining_cancel_in_days_free';
                    $replace = [
                        'shop_name' => $hotel_array[$v->dining_hotel_id]['name'],
                        'datetime' => $v->booking_datetime,
                        'days' => $v->cancel_days,
                        'order_no' => $v->order_number,
                        'username' => $user['name'],
                        'amount' => $v->charging * $v->number,
                    ];
                    RegularPushJob::dispatch($v->user_id, $key, $replace);

                    Mail::to($user['email'])->send(new Notice($key, $replace));

                }
        });

        Log::info('餐旅可免費取消天數的前一天提醒-結束');

    }
}
