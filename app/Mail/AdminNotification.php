<?php

namespace App\Mail;

use App\Models\Backend\Admin\Admin;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 忘記密碼通知管理員
 */
class AdminNotification extends BaseMail
{


    /**
     * Create a new message instance.
     *
     * @param Admin $admin
     *
     * @return void
     */
    public function __construct(
        public Admin $admin
    ) {
        parent::__construct();
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this->subject('管理員忘記密碼通知信')->view('mail.found_notification');
    }
}
