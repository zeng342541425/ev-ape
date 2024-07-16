<?php

namespace App\Console\Commands;

use App\Jobs\EmailJob;
use App\Mail\Notice;
use App\Models\Common\DiningBooking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingSupplementaryPaymentTips extends Command
{
    /**
     * 餐廳飯店沒有補繳金額通知
     *
     * @var string
     */
    protected $signature = 'BookingSupplementaryPaymentTips';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '餐廳飯店沒有補繳金額通知';

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

        Log::info('餐廳飯店沒有補繳金額通知-開始');

        // 餐廳飯店沒有補繳金額通知
        DiningBooking::query()->whereIn('payment_status', [3])->with('user:id,email,name')
            ->chunkById(200, function ($booking) {
            foreach($booking as $v) {

                try {

                    $_data = [
                        'username' => $v->user->name,
                        'shop_name' => $v->name,
                        'datetime' => $v->booking_date,
                        'time' => $v->time,
                        'amount' => $v->charging * $v->number,
                        'order_no' => $v->order_number,
                    ];
                   // EmailJob::dispatch($v->user->email, $_data, 10);
                    Mail::to($v->user->email)->send(new Notice("dining_supplementary_payment", $_data));
                } catch (\Throwable $e) {
                    Log::info('餐廳飯店沒有補繳金額通知error: ' . $e->getMessage());
                }

            }
        });

        Log::info('餐廳飯店沒有補繳金額通知-結束');

    }
}
