<?php

namespace App\Console\Commands;

use App\Jobs\EmailJob;
use App\Mail\Notice;
use App\Models\Common\DiningBooking;
use App\Models\Order\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OrderSupplementaryPaymentTips extends Command
{
    /**
     * 停車充電/餐廳飯店沒有補繳金額通知
     *
     * @var string
     */
    protected $signature = 'OrderSupplementaryPaymentTips';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '停車充電沒有補繳金額通知';

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

        Log::info('停車充電沒有補繳金額通知-開始');

        // 餐廳飯店沒有補繳金額通知
        Order::query()->whereIn('charging_status', [1])->where('status', 3)->with('user:id,email,name')
            ->chunkById(200, function ($booking) {
            foreach($booking as $v) {

                try {

                    $_data = [
                        'username' => $v->user->name,
                        'shop_or_parking_name' => $v->parking_lot_name,
                        'ending_time' => $v->ending_time,
                        'starting_time' => $v->starting_time,
                        'amount' => $v->amount,
                        'order_no' => $v->order_number,
                    ];
                   // EmailJob::dispatch($v->user->email, $_data, 8);

                    Mail::to($v->user->email)->send(new Notice("order_supplementary_payment", $_data));

                } catch (\Throwable $e) {
                    Log::info('停車充電沒有補繳金額通知error: ' . $e->getMessage());
                }

            }
        });

        Log::info('停車充電沒有補繳金額通知-結束');

    }
}
