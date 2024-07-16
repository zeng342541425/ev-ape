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

class FirebaseService
{

    use ReturnJson;

    protected Messaging $messaging;

    public string $member_all = '';

    public function __construct()
    {

        $file = config('firebase');
        $this->member_all = env('FIREBASE_ENV', 'pro');

        $factory = new Factory();
        $this->messaging = $factory->withServiceAccount($file)->createMessaging();
    }

    /**
     * 發送特定設備
     *
     * @param $deviceToken //特定設備id
     * @param $title //標題
     * @param $body //內容
     * @param array $data //內容
     * @throws FirebaseException
     * @throws MessagingException
     * @throws Throwable
     */
    public function sendSpecificDevice($deviceToken, $title, $body, array $data = []): void
    {
        try {
            $this->messaging->send($this->rawMessageFromArray($title, $body, 'specific_device', $deviceToken, $data));
        } catch (Throwable $e) {
            Log::error('firebase發送特定設備錯誤：' . $e->getMessage());
            //throw $e;
        }
    }

    /**
     * 發送多個設備
     *
     * @param $deviceTokens
     * @param $title
     * @param $body
     * @param array $data
     * @throws FirebaseException
     * @throws MessagingException
     * @throws Throwable
     */
    public function sendMultipleDevices($deviceTokens, $title, $body, array $data = []): void
    {
        try {
            $this->messaging->sendMulticast(
                $this->rawMessageFromArray($title, $body, 'multiple_device', '', $data),
                $deviceTokens
            );

        } catch (Throwable $e) {
            Log::error('firebase發送多個設備錯誤：' . $e->getMessage());
           // throw $e;
        }
    }

    /**
     * 發送主題
     *
     * @param $topic
     * @param $title
     * @param $body
     * @param array $data
     * @throws FirebaseException
     * @throws MessagingException
     * @throws Throwable
     */
    public function sendTopic($topic, $title, $body, array $data = []): void
    {
        try {
            $this->messaging->send($this->rawMessageFromArray($title, $body, 'topic', $topic, $data));
        } catch (Throwable $e) {
            Log::error('firebase發送主題錯誤：' . $e->getMessage());
           // throw $e;
        }
    }

    /**
     *
     * @param $title
     * @param $body
     * @param $type //類型 1：特定設備 2：多個設備 3：主題
     * @param string $deviceOrTopic 類型為1時對應特定設備id 類型為2時為空 類型為3時為主題
     * @param array $data //額外數據
     * @return RawMessageFromArray
     */
    public function rawMessageFromArray($title, $body, $type, string $deviceOrTopic = '', array $data = []): RawMessageFromArray {
        $array = [
            'android' => [
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default'
                ],
                'data' => array_merge($data,[
                    'badge' => '1'
                ])
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'alert' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'badge' => 1,
                        'sound' => 'default',
                    ],
                    'data' => $data,
                ]
            ]
        ];

        if ($type == 'specific_device') {
            $array['token'] = $deviceOrTopic;
        } else {
            if ($type == 'topic') {
                $array['topic'] = $deviceOrTopic;
            }
        }
        Log::info('FB數據', ['data' => $array]);
        return new RawMessageFromArray($array);
    }

    /**
     * 單個設備訂閱單個主題
     *
     * @param string $deviceToken
     * @throws Throwable
     */
    public function subTopics(string $deviceToken): void
    {

        $topics = [$this->member_all];

        try {
            $this->messaging->subscribeToTopics($topics, $deviceToken);
        } catch (Throwable $e) {
            Log::error($e->getMessage());
            //throw $e;
        }
    }

    /**
     * 退訂主題
     * @param array $tokens //設備token
     * @param string $specificTopic //特定主題/特定主題前綴
     * @throws FirebaseException
     * @throws Throwable
     */
    // public function unsubTopics(array $tokens, string $specificTopic = ''): void
    // {
    //     foreach ($tokens as $token) {
    //         $subTopics = [];
    //
    //         try {
    //             $appInstance = $this->messaging->getAppInstance($token);
    //
    //             $subscriptions = $appInstance->topicSubscriptions();
    //
    //             foreach ($subscriptions as $subscription) {
    //                 $subTopic = $subscription->topic();
    //
    //                 if (!$specificTopic || $subTopic == $specificTopic) {
    //                     $subTopics[] = $subTopic;
    //                 }
    //             }
    //
    //             if ($subTopics) {
    //                 $this->messaging->unsubscribeFromTopics($subTopics, $token);
    //             }
    //         } catch (Throwable $e) {
    //             Log::error($e->getMessage());
    //             throw $e;
    //         }
    //     }
    // }

    public function unsubTopics(array $tokens, string $specificTopic = ''): void
    {
        foreach ($tokens as $token) {

            try {

                $topics = [$this->member_all];
                $this->messaging->unsubscribeFromTopics($topics, $token);

            } catch (Throwable $e) {
                Log::error($e->getMessage());
               // throw $e;
            }
        }
    }

    /**
     * 發送全體用戶
     * @param string $title
     * @param string $body
     * @param array $data
     * @return void
     * @throws FirebaseException
     * @throws MessagingException
     * @throws Throwable
     */
    public function sendAllMember(string $title, string $body, array $data = []): void
    {
        $this->sendTopic($this->member_all, $title, $body, $data);
    }

}
