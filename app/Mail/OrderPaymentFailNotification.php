<?php

namespace App\Mail;

use App\Models\Backend\Admin\Admin;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 充電刷卡失敗
 */
class OrderPaymentFailNotification extends BaseMail
{


    public $subject = 'EV APE：充電未完成繳費，請查明';
    public $user_name;
    public $starting_time;
    public $ending_time;
    public $time;
    public $parking_lot_name;
    public $amount;

    /**
     * Create a new message instance.
     *
     * @param $user_name
     * @param $parking_lot_name
     * @param $date
     * @param $time
     * @param $amount
     */
    public function __construct($user_name, $parking_lot_name, $starting_time, $ending_time, $amount) {
        parent::__construct();
        $this->parking_lot_name = $parking_lot_name;
        $this->starting_time = $starting_time;
        $this->ending_time = $ending_time;
        $this->amount = $amount;
        $this->user_name = $user_name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this->subject($this->subject)->view('mail.order_payment_fail');
    }
}
