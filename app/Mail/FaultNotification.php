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
class FaultNotification extends BaseMail
{


    public $subject = '【EV AEP客服】{created_at}{user_name}提報了問題';
    public $user_name;
    public $created_at;
    public $user_phone;
    public $category_name;
    public $content;

    /**
     * Create a new message instance.
     *
     * @param $user_name
     * @param $user_phone
     * @param $created_at
     * @param $category_name
     * @param $content
     */
    public function __construct($user_name, $user_phone, $created_at, $category_name, $content)
    {
        parent::__construct();
        $this->subject = str_replace(
            ['{created_at}', '{user_name}'],
            [$created_at, $user_name], $this->subject);

        $this->user_name = $user_name;
        $this->created_at = $created_at;
        $this->user_phone = $user_phone;
        $this->category_name = $category_name;
        $this->content = $content;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build(): static
    {
        return $this->subject($this->subject)->view('mail.fault');
    }
}
