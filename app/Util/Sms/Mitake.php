<?php

namespace App\Util\Sms;

use Illuminate\Support\Facades\Log;

class Mitake implements SmsInterface
{

    protected string $username;
    protected string $password;
    protected string $domain;

    public function __construct()
    {
        $this->username = config('sms.mitake.user');
        $this->password = config('sms.mitake.pass');
        $this->domain = config('sms.mitake.domain');
    }

    public function send(string $phone, string $content,array $options): bool
    {
        $url = $this->domain . '/api/mtk/SmSend?';
        $url .= 'CharsetURL=UTF-8';
        // parameters
        $data = '&username=' . $this->username;
        $data .= '&password=' . $this->password;
        $data .= '&dstaddr=' . $phone;
        $data .= '&smbody=' . urlencode($content);

        try {
            Log::info("mitake發送", ['url' => $url, 'data' => $data]);

            $res = file_get_contents($url . $data);
            Log::info("mitake結果", ['res' => $res]);
            return true;
        } catch (\Exception $exception) {
            report($exception);
        }
        return false;
    }

}
