<?php

namespace App\Util\Thirdly;



use AppleSignIn\ASDecoder;

class Apple implements ThirdlyInterface
{

    /**
     * @return array|string|null
     * @throws \Exception
     */
    public function getOpenId(): array|string|null
    {
        $open_id = request()->post('open_id');
        $identityToken = request()->post('access_token');
        $appleSignInPayload = ASDecoder::getAppleSignInPayload($identityToken);

        if (!$appleSignInPayload->verifyUser($open_id)) {
            throw new \Exception('apple帳號認證失敗');
        }
        return $open_id;

    }

}
