<?php

namespace App\Jobs;

use App\Models\Common\DiningBooking;
use App\Models\Common\FirebaseNotice;
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
class RegularPushJob extends BaseJob
{

    public int $tries = 1;

    private $user_id = 0;
    private $key = '';
    private $replace = [];

    public string $name = "固定推播隊列";
    public string $desc = "固定推播隊列";

    public function __construct($user_id = 0, $key = '', $replace = [])
    {

        parent::__construct();

        $this->onQueue('regular_push');

        $this->user_id = $user_id;
        $this->key = $key;
        $this->replace = $replace;

    }

    public function handle()
    {

        Log::info('固定推播隊列-開始', ['data' => ['user_id' => $this->user_id, 'key' => $this->key]]);
        $firebase = FirebaseNotice::query()->where('key', $this->key)->first();
        if ($firebase) {

            if (!empty($this->replace)) {
                foreach ($this->replace as $key => $val) {
                    $firebase['content'] = str_replace('{' . $key . '}', $val, $firebase['content']);
                }
            }


            if (is_array($this->user_id)) {

                foreach($this->user_id as $uid) {
                    $tmp = [
                        'user_id' => $uid,
                        'title' => $firebase['title'],
                        'published_at' => date('Y-m-d H:i:s'),
                        'content' => $firebase['content'],
                        'brief_introduction' => $firebase['content'],
                        'reading' => 0,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    $firebase_tokens =  UserFirebase::query()->where('user_id',$uid)->pluck('firebase_token')->toArray();
                   $id = UserNotice::query()->insertGetId($tmp);
                    (new NoticeService())->send($firebase_tokens, $firebase,['type'=>"2",'object_id' => (string)$id]);
                }
            } else {
                $tmp = [
                    'user_id' => $this->user_id,
                    'title' => $firebase['title'],
                    'published_at' => date('Y-m-d H:i:s'),
                    'content' => $firebase['content'],
                    'brief_introduction' => $firebase['content'],
                    'reading' => 0,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
                $firebase_tokens =  UserFirebase::query()->where('user_id',$this->user_id)->pluck('firebase_token')->toArray();
                $id = UserNotice::query()->insertGetId($tmp);
                (new NoticeService())->send($firebase_tokens, $firebase,['type'=>"2",'object_id' => (string)$id]);
            }

        }

        Log::info('固定推播隊列-結束', ['data' => ['user_id' => $this->user_id, 'key' => $this->key]]);

    }
}
