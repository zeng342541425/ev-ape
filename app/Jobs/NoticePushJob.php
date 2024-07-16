<?php

namespace App\Jobs;

use App\Models\Common\DiningBooking;
use App\Models\Common\UserNotice;
use App\Models\Frontend\User\User;
use App\Services\Common\FirebaseService;
use App\Services\Common\InvoiceService;
use App\Services\Common\NoticeService;
use App\Services\Common\PaymentService;
use Illuminate\Support\Facades\Log;

/**
 * 公告推播隊列
 */
class NoticePushJob extends BaseJob
{

    public int $tries = 1;

    private $messageModel;

    public string $name = "公告推播隊列";
    public string $desc = "公告推播隊列";

    public function __construct($messageModel = null)
    {

        parent::__construct();

        $this->onQueue('notice_push');

        $this->messageModel = $messageModel;

    }

    public function handle()
    {

        $messageModel = $this->messageModel;
        User::query()->select('id')->where('status', 1)
            ->chunkById(200, function ($users) use ( $messageModel) {

                $tmp = [];
                foreach($users as $v) {
                    $tmp[] = [
                        'user_id' => $v['id'],
                        'object_id' => $messageModel['id'],
                        'title' => $messageModel['title'],
                        'published_at' => $messageModel['send_time'],
                        'content' => $messageModel['content'],
                        'brief_introduction' => $messageModel['content'],
                        'reading' => 0,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                }

                UserNotice::query()->insert($tmp);

            });

        (new NoticeService())->sendBatch($this->messageModel,['type' => "3" ,'object_id' => (string)$messageModel['id']]);

    }
}
