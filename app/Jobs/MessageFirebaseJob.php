<?php

namespace App\Jobs;

use App\Models\Common\DiningBooking;
use App\Models\Common\FirebaseNotice;
use App\Models\Common\Message;
use App\Models\Common\UserFirebase;
use App\Models\Common\UserNotice;
use App\Models\Frontend\User\User;
use App\Services\Common\FirebaseService;
use App\Services\Common\InvoiceService;
use App\Services\Common\NoticeService;
use App\Services\Common\PaymentService;
use Illuminate\Support\Facades\Log;

/**
 * 固定推播隊列
 */
class MessageFirebaseJob extends BaseJob
{

    public int $tries = 1;


    public $message;

    public string $name = "最新消息推播";
    public string $desc = "最新消息推播";

    public function __construct(Message $message)
    {

        parent::__construct();

        $this->onQueue('regular_push');

        $this->message = $message;


    }

    public function handle()
    {

        Log::info('最新消息推播隊列-開始', ['data' => ['id' => $this->message->id]]);

        if (empty($this->message->status) || $this->message->status != 1) return;

        Log::info('111111');
        $this->message->content = $this->message->brief_introduction;
        (new NoticeService())->sendBatch($this->message, ['type' => "1", 'object_id' => (string)$this->message->id]);


        Log::info('最新消息推播隊列-結束', ['data' => ['id' => $this->message->id]]);

    }
}
