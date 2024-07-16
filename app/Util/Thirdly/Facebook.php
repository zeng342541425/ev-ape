<?php

namespace App\Util\Thirdly;


class Facebook implements ThirdlyInterface
{

    protected $appid;
    protected $secret;

    public function __construct()
    {
        $this->setConfig();
    }

    public function setConfig(){

        $config = config('thirdly.facebook');
        $this->appid = $config['appid'];
        $this->secret = $config['secret'];
    }

    public function getOpenId()
    {
        $str = "$this->appid|$this->secret";
        $token = request()->post('access_token');
        $url = "https://graph.facebook.com/debug_token?access_token={$str}&input_token=".$token;
        $result = json_decode(file_get_contents($url), true);
        if (!$result['data']['is_valid']) throw new \Exception('facebook帳號認證失敗');
        return $result['data']['user_id'];
    }

}
