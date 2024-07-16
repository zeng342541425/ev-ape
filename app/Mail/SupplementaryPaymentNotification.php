<?php

namespace App\Mail;

use App\Models\Backend\Admin\Admin;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 停車充電、餐廳飯店沒有補繳金額 通知用戶
 */
class SupplementaryPaymentNotification extends BaseMail
{


    public $subject = 'EV APE：您有一筆充電交易催繳通知';
    public $user_name;
    public $shop_or_parking_name;
    public $starting_time;
    public $ending_time;
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
    public function __construct($user_name, $shop_or_parking_name, $starting_time, $ending_time, $amount, $order_no)
    {
        parent::__construct();
        $this->shop_or_parking_name = $shop_or_parking_name;
        $this->starting_time = $starting_time;
        $this->ending_time = $ending_time;
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
        return $this->subject($this->subject)->view('mail.supplementary_payment');
    }
}
