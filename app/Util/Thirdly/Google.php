<?php

namespace App\Util\Thirdly;


class Google implements ThirdlyInterface
{

    public function getOpenId(): string
    {
        try {
            $url = "https://oauth2.googleapis.com/tokeninfo?id_token=".request()->post('access_token');
            $result = json_decode(file_get_contents($url), true);

            if (!isset($result['sub'])) return "";

            return $result['sub'];

        }catch (\Exception $exception){

            throw new \Exception("google帳號認證异常");
        }

    }

}
