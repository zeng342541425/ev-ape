<?php

namespace App\Services\Common;

use App\Traits\ReturnJson;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\RawMessageFromArray;
use Kreait\Firebase\Contract\Messaging;
use Throwable;

class NoticeService
{

    use ReturnJson;

    /**
     * @param $msg
     * @param array $data
     * @return void
     * @throws FirebaseException
     * @throws MessagingException
     * @throws Throwable
     */
    public function sendBatch($msg,array $data = [])
    {
        (new FirebaseService())->sendAllMember($msg['title'], $msg['content'],$data);
    }

    public function send($firebase_tokens, $msg,$data = [])
    {
        (new FirebaseService())->sendMultipleDevices($firebase_tokens, $msg['title'], $msg['content'],$data);
    }
}
