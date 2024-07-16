<?php

namespace App\Mail;

use App\Models\Backend\Admin\Admin;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 郵箱驗證
 */
class DiningSuccessNotification extends BaseMail
{


    public $subject = '感謝您！您在EV APE預訂的{shop_name}訂單已完成';
    public $user_name;
    public $shop_name;
    public $month;
    public $day;
    public $time;
    public $number;

    /**
     * Create a new message instance.
     *
     * @param $user_name
     * @param $shop_name
     * @param $month
     * @param $day
     * @param $time
     * @param $number
     */
    public function __construct($user_name, $shop_name, $month, $day, $time, $number) {
        parent::__construct();
        $this->shop_name = $shop_name;
        $this->subject = str_replace('{shop_name}', $shop_name, $this->subject);

        $this->month = $month;
        $this->day = $day;
        $this->time = $time;
        $this->number = $number;
        $this->user_name = $user_name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this->subject($this->subject)->view('mail.dining_success');
    }
}
