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
class VerifyNotification extends BaseMail
{

    public $subject = '歡迎加入EV APE！請驗證您的e-mail信箱';
    public $code;

    /**
     * Create a new message instance.
     *
     * @param Admin $admin
     *
     * @return void
     */
    public function __construct($code) {
        parent::__construct();
        $this->code = $code;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this->subject($this->subject)->view('mail.verify');
    }
}
