<?php

namespace App\Util\Thirdly;

use GuzzleHttp\Client;

class Line implements ThirdlyInterface
{

    public function getOpenId()
    {
        $access_token = request()->post('access_token');
        $url = "https://api.line.me/v2/profile";
        $r = (new Client())->get($url,[ 'headers' => ["Authorization"=>"Bearer $access_token"]]);

        $data = json_decode($r->getBody()->getContents(),true);
        return $data['userId'];
    }

}
