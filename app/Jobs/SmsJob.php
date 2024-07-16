<?php

namespace App\Jobs;


use App\Util\Sms\MitakeBtcSms;


class SmsJob extends BaseJob
{

    public int $tries = 3;

    private string $phone;
    private string $content;

    public string $name = "簡訊王";
    public string $desc = "發送簡訊";

    public function __construct(string $phone = '', string $content = '')
    {

        parent::__construct();

        $this->onQueue('sms');

        $this->phone = $phone;
        $this->content = $content;
    }

    public function handle()
    {
        $res = MitakeBtcSms::make()->smSend($this->phone, $this->content);
        if (!isset($res[1]['statuscode']) || !in_array($res[1]['statuscode'], [0, 1, 2, 4])) {
            throw new \Exception('驗證碼發送失敗');
        }
    }
}
