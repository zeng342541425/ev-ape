<?php

namespace App\Console\Commands;

use App\Jobs\Payment;
use App\Mail\Notice;
use App\Mail\OrderPaymentFailNotification;
use App\Mail\VerifyNotification;
use App\Models\Common\Banner;
use App\Models\Frontend\User\User;
use App\Models\Order\Order;
use App\Services\Common\EmailCodeService;
use App\Services\Common\InvoiceService;
use App\Services\Common\PileService;
use App\Util\Sms\MitakeBtcSms;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;

class Test extends Command
{
    /**
     * 自動下架組合商品
     *
     * @var string
     */
    protected $signature = 'test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'test';

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


       dd(11);
      //  Mail::to("619891420@qq.com")->send(new Notice("dining_booking_success_shop", ['test'=>'測試數據']));
        Mail::to("penny@casaloma.cc")->send(new Notice("dining_booking_success_shop", ['test'=>'測試數據']));
        dd(11);

        $clientId = 'hkjy87687';
        $user_id = 1211111;
        // Redis::sadd('workerMan:userId:'.$user_id, $clientId);
        // Redis::expire('workerMan:userId:'.$user_id, 1000 * 24 * 3600);
        //
        // Redis::setex('workerMan:clientId:' . $clientId, 1000 * 3600, $user_id);

        $clientIds = Redis::smembers('workerMan:userId:'. $user_id);
        var_dump($clientIds);

        // $user_id = Redis::get('workerMan:clientId:' . $clientId);
        // var_dump($user_id);
        //
        // Redis::srem('workerMan:userId:'.$user_id, $clientId);
        // Redis::del('workerMan:clientId:' . $clientId);
        exit();

        $email = '379120787@qq.com';
        $code = '1215';
        Mail::to($email)->send(new VerifyNotification($code));
        exit();
        // Payment::dispatch(0, 10, 1);
        // $code = (new PileService())->register('A10979');
        // var_dump($code);

        echo date('Y-m-d 00:00:00', strtotime('+3 day'));exit();

        $user_id = 13;
        $order_id = 56;
        $user = User::query()->where('id', $user_id)->first();
        $order_info = Order::query()->where('id', $order_id)->first();
        $res = (new InvoiceService())->sendOrder($order_info, $user);
        if ($res && empty($res['invoice_number'])) {
            $update = [
                'invoice_number' => $res['invoice_number'],
            ];
            Order::query()->where('id', $order_id)->update($update);
        }
        var_dump($res);
        exit();

        $str = '{"invoice_info":{"title":"\u53f0\u7063\u52d5\u7269\u6cd5\u5f8b\u6276\u52a9\u5354\u6703","tax_id":"82325980"}}';
        $in_info = json_decode($str, true);
        print_r($in_info);
        exit();
        $data['email'] = 'penny@casaloma.cc';
        (new EmailCodeService)->send($data);

    }
}
