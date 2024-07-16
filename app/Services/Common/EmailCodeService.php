<?php

namespace App\Services\Common;

use App\Mail\BookingCancelNotification;
use App\Mail\BookingManageCancelNotification;
use App\Mail\BookingNotification;
use App\Mail\DiningSuccessNotification;
use App\Mail\DiningSupplementaryPaymentNotification;
use App\Mail\FaultNotification;
use App\Mail\FaultSubmitNotification;
use App\Mail\Notice;
use App\Mail\OrderPaymentFailNotification;
use App\Mail\SupplementaryPaymentNotification;
use App\Mail\VerifyNotification;
use App\Models\Common\EmailCode;
use App\Traits\ReturnJson;
use Illuminate\Support\Facades\Mail;

class EmailCodeService
{

    use ReturnJson;

    public mixed $email = '';
    public int $type = 1;
    public array $data = [];

    public function __construct($email = '', $data = [], $type = 1)
    {
        $this->email = $email;
        $this->data = $data;

        $this->type = $type;
    }

    /**
     * 發送信件
     *
     */
    public function send()
    {

        switch ($this->type) {

            case 2:
                $this->diningSuccess($this->data);
                break;

            case 3:
                $this->faultSubmit($this->data);
                break;

            case 4:
                $this->orderPaymentFail($this->data);
                break;

            case 5:
                $this->booking($this->data);
                break;

            case 6:
                $this->bookingCancel($this->data);
                break;

            case 7:
                $this->fault($this->data);
                break;

            case 8:
                $this->supplementaryPayment($this->data);
                break;

            case 9:
                $this->bookingManageCancel($this->data);
                break;

            case 10:
                $this->diningSupplementaryPayment($this->data);
                break;

            case 1:
            default:
                $this->verify();
                break;
        }

    }

    // 發送驗證碼
    public function verify(): string
    {
        $config = config("email");

        $expire = $config['expired_time'];

        $insert_data['email'] = $this->email;

        // 第三方發送驗證碼代碼
        $debug = config('evape.email_code_debug');

        $code = $debug ? '1234' : Common::nonceRandom(4, 2);
        $insert_data['code'] = $code;
        $insert_data['expired_time'] = time() + $expire;

        EmailCode::query()->create($insert_data);

        // 調試模式，如果true, 不會發送sms
        if ( !$debug ) {
            // 對接第三方接口發送
            // $content = str_replace('{code}', $code, $config['content']);
            $email = $this->email;
            Mail::to($email)->send(new VerifyNotification($code));

            // EmailJob::dispatch($data['phone'], $content);
        }
        return $code;
    }



    public static function get_code($email): string
    {
        $config = config("email");

        $expire = $config['expired_time'];

        $insert_data['email'] = $email;

        // 第三方發送驗證碼代碼
        $debug = config('evape.email_code_debug');

        $code = $debug ? '1234' : Common::nonceRandom(4, 2);
        $insert_data['code'] = $code;
        $insert_data['expired_time'] = time() + $expire;

        EmailCode::query()->create($insert_data);

        // 調試模式，如果true, 不會發送sms
        if ( !$debug ) {
            // 對接第三方接口發送
            // $content = str_replace('{code}', $code, $config['content']);
            $__data = [
                'username' => request()->user()['name'],
                'code' => $code,
            ];

            Mail::to($email)->send(new Notice("user_verify", $__data));
           // Mail::to($email)->send(new VerifyNotification($code));

            // EmailJob::dispatch($data['phone'], $content);
        }
        return $code;
    }

    // 餐廳預約成功
    public function diningSuccess($data): void
    {
        $email = $this->email;
        $user_name = $data['user_name'];
        $shop_name = $data['shop_name'];
        $month = $data['month'];
        $day = $data['day'];
        $time = $data['time'];
        $number = $data['number'];
        Mail::to($email)->send(new DiningSuccessNotification($user_name, $shop_name, $month, $day, $time, $number));
    }

    // 客服表單填寫後
    public function faultSubmit($data): void
    {
        $user_name = $data['user_name'];
        $content = $data['content'];
        Mail::to($this->email)->send(new FaultSubmitNotification($user_name, $content));
    }

    // 充電刷卡失敗
    public function orderPaymentFail($data): void
    {
        $user_name = $data['user_name'];
        $parking_lot_name = $data['parking_lot_name'];
        $starting_time = $data['starting_time'];
        $ending_time = $data['ending_time'];
        $amount = $data['amount'];
        Mail::to($this->email)->send(new OrderPaymentFailNotification($user_name, $parking_lot_name, $starting_time, $ending_time, $amount));
    }

    // 餐廳/飯店 預約成功 通知商家
    public function booking($data): void
    {
        // $user_name, $shop_name, $number, $booking_date, $time, $user_phone, $order_no, $amount
        $user_name = $data['user_name'];
        $shop_name = $data['shop_name'];
        $number = $data['number'];
        $booking_date = $data['booking_date'];
        $time = $data['time'];
        $user_phone = $data['user_phone'];
        $order_no = $data['order_no'];
        $amount = $data['amount'];
        Mail::to($this->email)->send(new BookingNotification($user_name, $shop_name, $number, $booking_date, $time, $user_phone, $order_no, $amount));
    }

    // 餐廳/飯店 預約取消 通知商家
    public function bookingCancel($data): void
    {
        // $user_name, $shop_name, $number, $booking_date, $time, $user_phone, $order_no, $amount
        $user_name = $data['user_name'];
        $shop_name = $data['shop_name'];
        $number = $data['number'];
        $booking_date = $data['booking_date'];
        $time = $data['time'];
        $user_phone = $data['user_phone'];
        $order_no = $data['order_no'];
        $amount = $data['amount'];
        Mail::to($this->email)->send(new BookingCancelNotification($user_name, $shop_name, $number, $booking_date, $time, $user_phone, $order_no, $amount));
    }

    // 客服表單填寫後 通知商家
    public function fault($data): void
    {
        // $user_name, $user_phone, $created_at, $category_name, $content
        $user_name = $data['user_name'];
        $created_at = $data['created_at'];
        $category_name = $data['category_name'];
        $content = $data['content'];
        $user_phone = $data['user_phone'];
        Mail::to($this->email)->send(new FaultNotification($user_name, $user_phone, $created_at, $category_name, $content));
    }

    // 餐廳飯店沒有補繳金額 通知用戶
    public function supplementaryPayment($data): void
    {
        // $user_name, $shop_or_parking_name, $datetime, $time, $amount
        $user_name = $data['user_name'];
        $shop_or_parking_name = $data['shop_or_parking_name'];
        $starting_time = $data['starting_time'];
        $ending_time = $data['ending_time'];
        $amount = $data['amount'];
        $order_no = $data['order_no'];
        Mail::to($this->email)->send(new SupplementaryPaymentNotification($user_name, $shop_or_parking_name, $starting_time, $ending_time, $amount, $order_no));
    }

    // 後台操作 餐廳/飯店 預約取消 通知商家
    public function bookingManageCancel($data): void
    {
        // $user_name, $shop_name, $number, $booking_date, $time, $user_phone, $order_no, $amount
        $user_name = $data['user_name'];
        $shop_name = $data['shop_name'];
        $number = $data['number'];
        $booking_date = $data['booking_date'];
        $time = $data['time'];
        $user_phone = $data['user_phone'];
        $order_no = $data['order_no'];
        $amount = $data['amount'];
        Mail::to($this->email)->send(new BookingManageCancelNotification($user_name, $shop_name, $number, $booking_date, $time, $user_phone, $order_no, $amount));
    }

    // 餐廳飯店沒有補繳金額 通知用戶
    public function diningSupplementaryPayment($data): void
    {
        // $user_name, $shop_or_parking_name, $datetime, $time, $amount
        $user_name = $data['user_name'];
        $shop_or_parking_name = $data['shop_or_parking_name'];
        $datetime = $data['datetime'];
        $time = $data['time'];
        $amount = $data['amount'];
        $order_no = $data['order_no'];
        Mail::to($this->email)->send(new DiningSupplementaryPaymentNotification($user_name, $shop_or_parking_name, $datetime, $time, $amount, $order_no));
    }

}
