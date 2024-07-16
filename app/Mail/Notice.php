<?php

namespace App\Mail;

use App\Models\Backend\Admin\Admin;
use App\Models\Common\EmailNotices;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * 郵箱驗證
 */
class Notice extends BaseMail
{

    public $subject;
    public $content;
    public $data;

    /**
     * Create a new message instance.
     *
     * @param Admin $admin
     *
     * @return void
     */
    public function __construct($key,$data=[]) {
        $notice = EmailNotices::query()->where('key',$key)->first();
        $subject = $notice ? $notice->title : "";
        foreach ($data as $key => $val) {
            $subject = str_replace('{' . $key . '}', $val, $subject);
        }
        $this->subject = $subject;

        $content = $notice ? $notice->content : "";

        foreach ($data as $key => $val) {
            $content = str_replace('{' . $key . '}', $val, $content);
        }
        $this->content = $content;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this->subject($this->subject)->view('mail.notice');
    }
}
