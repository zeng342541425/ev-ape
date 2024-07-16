<?php

namespace App\Mail;

use App\Models\Backend\Admin\Admin;
use App\Models\Common\EmailNotices;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;


abstract class BaseMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct()
    {
        $this->afterCommit();
        $this->onQueue('email_job');
    }


}
