<?php

namespace App\Console\Commands;

use App\Jobs\RegularPushJob;
use App\Mail\Notice;
use App\Models\Common\Banner;
use App\Models\Common\DiningBooking;
use App\Models\Frontend\User\User;
use App\Services\Common\InvoiceService;
use App\Services\Common\PaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DiningCancel extends Command
{
    /**
     * 自動下架組合商品
     *
     * @var string
     */
    protected $signature = 'DiningCancel';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '餐旅預約';

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

        Log::info('餐旅預約自動取消-開始');

        // 點數活動時間過期自動下架
        $current_datetime = date('Y-m-d H:i:s', strtotime('-4 hour'));
        $pay_service = new PaymentService();
        DiningBooking::query()->whereIn('status', [0])
            ->where('booking_datetime', '<=', $current_datetime)
            ->chunkById(200, function ($users) use ( $pay_service) {
            foreach($users as $v) {

                try {
                    $user = User::query()->where('id', $v->user_id)->first();
                    $replace = [
                        'amount' => $v->number * $v->charging,
                        'order_no' => $v->order_number,
                        'booking_name' => $v->booking_name,
                        'shop_name' => $v->name,
                        'username' => $user['name'],
                    ];
                    if ($pay_service->payDining($v->id, $v->user_id, 'payment_dining_has_not_left_notify')) {
                        $r = DiningBooking::query()->where('id', $v->id)->update([
                            'status' => 2,
                            'payment_status' => 1,
                        ]);

                        // 開發票
                       // $user = User::query()->where('id', $v->user_id)->first();
                        $res = (new InvoiceService())->send($v, $user);
                        if ($res) {
                            !empty($res['invoice_number']) && DiningBooking::query()->where('id', $v->id)->update([
                                'invoice_number' => $res['invoice_number'],
                            ]);
                        }

                        // 推播
                        $key = 'dining_expired_payment_success';
                    } else {
                        // 推播
                        $key = 'dining_expired_payment_error';

                        $r = DiningBooking::query()->where('id', $v->id)->update([
                            'status' => 2,
                            'payment_status' => 3,
                        ]);

                    }

                    RegularPushJob::dispatch($v->user_id, $key, $replace);

                    Mail::to($user['email'])->send(new Notice($key, $replace));
                } catch (\Throwable $e) {
                    Log::info('餐旅預約自動取消error: ' . $e->getMessage());
                }

            }
        });

        Log::info('餐旅預約自動取消-結束');

    }
}
