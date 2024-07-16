<?php

namespace App\Mail;

use App\Models\Backend\Admin\Admin;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 收到客服表單 通知用戶
 */
class FaultSubmitNotification extends BaseMail
{


    public $subject = 'EV APE客服中心已經收到您反應的問題';
    public $user_name;
    public $content;

    /**
     * Create a new message instance.
     *
     * @param $user_name
     * @param $content
     */
    public function __construct($user_name, $content) {
        parent::__construct();
        $this->user_name = $user_name;
        $this->content = $content;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this->subject($this->subject)->view('mail.fault_submit');
    }
}
