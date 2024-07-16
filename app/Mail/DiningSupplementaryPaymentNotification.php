<?php

namespace App\Mail;

use App\Models\Backend\Admin\Admin;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 餐廳飯店沒有補繳金額 通知用戶
 */
class DiningSupplementaryPaymentNotification extends BaseMail
{

    public $subject = 'EV APE：您有一筆餐旅交易催繳通知';
    public $user_name;
    public $shop_or_parking_name;
    public $datetime;
    public $time;
    public $order_no;
    public $amount;

    /**
     * Create a new message instance.
     *
     * @param $user_name
     * @param $shop_or_parking_name
     * @param $datetime
     * @param $time
     * @param $amount
     */
    public function __construct($user_name, $shop_or_parking_name, $datetime, $time, $amount, $order_no)
    {
        parent::__construct();
        $this->shop_or_parking_name = $shop_or_parking_name;
        $this->datetime = $datetime;
        $this->time = $time;
        $this->user_name = $user_name;
        $this->amount = $amount;
        $this->order_no = $order_no;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this->subject($this->subject)->view('mail.dining_supplementary_payment');
    }
}
