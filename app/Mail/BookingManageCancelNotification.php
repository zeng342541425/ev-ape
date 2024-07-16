<?php

namespace App\Mail;

use App\Models\Backend\Admin\Admin;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 後台餐廳取消 通知商家
 */
class BookingManageCancelNotification extends BaseMail
{

    // public $subject = '【EVAPE取消】{user_name}已取消{number}個{booking_date} {time}【{shop_name}】';
    public $subject = '【EVAPE取消】系統已取消{user_name} {number}個{booking_date} {time}【{shop_name}】';
    public $user_name;
    public $shop_name;
    public $number;
    public $booking_date;
    public $time;
    public $user_phone;
    public $order_no;
    public $amount;

    /**
     * Create a new message instance.
     *
     * @param $user_name
     * @param $shop_name
     * @param $number
     * @param $booking_date
     * @param $time
     * @param $user_phone
     * @param $order_no
     * @param $amount
     */
    public function __construct($user_name, $shop_name, $number, $booking_date, $time, $user_phone, $order_no, $amount) {
        parent::__construct();
        $this->subject = str_replace(
            ['{user_name}', '{number}', '{booking_date}', '{time}', '{shop_name}'],
            [$user_name, $number, $booking_date, $time, $shop_name], $this->subject);
        $this->shop_name = $shop_name;
        $this->number = $number;
        $this->time = $time;
        $this->booking_date = $booking_date;
        $this->user_name = $user_name;
        $this->user_phone = $user_phone;
        $this->order_no = $order_no;
        $this->amount = $amount;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this->subject($this->subject)->view('mail.booking_manage_cancel');
    }
}
