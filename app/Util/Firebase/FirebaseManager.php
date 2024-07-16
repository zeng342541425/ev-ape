<?php

namespace App\Util\Firebase;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\RawMessageFromArray;
use function App\Util\str_contains;


class FirebaseManager
{
    /**
     * @var \Kreait\Firebase\Contract\Messaging
     */
    protected \Kreait\Firebase\Contract\Messaging $messaging;

    /**
     * 主題前綴，區分環境
     * @var string
     */
    public string $topic_prefix;


    public function __construct(Factory $factory)
    {
        $file = config('firebase');
        $this->messaging = $factory->withServiceAccount($file)->createMessaging();
        $this->setTopicPrefix();
    }

    /**
     * @return void
     */
    protected function setTopicPrefix(): void
    {

        $this->topic_prefix = (config('app.env') == "production") ? "pro_" : "test_";

    }

    protected function getTopics(array|string $topics): array|string
    {
        if(is_string($topics)){
           return $this->topic_prefix . $topics;
        }
        $topicsArr = [];
        foreach ($topics as $v) {
            $topicsArr[] = $this->topic_prefix . $v;
        }
        return $topicsArr;

    }

    /**
     * @param array $tokens
     * @param string $title
     * @param string $body
     * @param array $data
     * @return void
     */
    public function sendByToken(array $tokens, string $title, string $body, array $data = []): void
    {
        if (empty($tokens)) return;
        if (count($tokens) == 1) {
            $this->sendSpecificDevice($tokens[0], $title, $body, $data);
        } else {
            $this->sendMultipleDevices($tokens, $title, $body, $data);
        }

    }

    /**
     * 發送特定設備
     *
     * @param string $deviceToken //特定設備id
     * @param $title //標題
     * @param $body //內容
     * @param $data //內容
     */
    protected function sendSpecificDevice(string $deviceToken, string $title, string $body, array $data = []): void
    {
        try {
            $this->messaging->send($this->rawMessageFromArray($title, $body, 'specific_device', $deviceToken, $data));
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
        }
    }

    /**
     * 發送多個設備
     *
     * @param $deviceTokens
     * @param $title
     * @param $body
     * @param array $data
     */
    protected function sendMultipleDevices($deviceTokens, $title, $body, array $data = []): void
    {
        $deviceTokens = array_chunk($deviceTokens,500);
        foreach ($deviceTokens as $token){
            try {
                $this->messaging->sendMulticast(
                    $this->rawMessageFromArray($title, $body, 'multiple_device', '', $data),
                    $token
                );
            } catch (\Throwable $e) {
                Log::error($e->getMessage());
            }
        }

    }

    /**
     * @param string|array $topics
     * @param string $title
     * @param string $body
     * @param array $data
     * @return void
     */
    public function sendByTopics(string|array $topics, string $title, string $body, array $data = []): void
    {
        if (is_string($topics)) $topics = [$topics];

        foreach ($this->getTopics($topics) as $v) {
            try {
                $this->messaging->send($this->rawMessageFromArray($title, $body, 'topic', $v, $data));
            } catch (\Throwable $e) {
                Log::error($e->getMessage());
            }
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
    protected function rawMessageFromArray(string $title, string $body, string $type, string $deviceOrTopic = '', array $data = []): RawMessageFromArray
    {
        $array = [
            'android' => [
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'sound' => 'default',
                ],
                'data' => array_merge($data, ['badge' => '1'])
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'alert' => [
                            'title' => $title,
                            'body' => $body
                        ],
                        'badge' => 1,
                        'sound' => 'default',
                    ],
                    'data' => $data
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

        return new RawMessageFromArray($array);
    }

    /**
     * 單個設備訂閱主題
     *
     * @param $deviceToken
     * @param $topics
     */
    public function subTopics(string $deviceToken, array $topics): void
    {
        try {
            $this->messaging->subscribeToTopics($this->getTopics($topics), $deviceToken);
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
        }


    }

    /**
     * 退訂主題
     * @param $tokens //設備token
     * @param $specificTopic //特定主題/特定主題前綴
     */
    public function unsubTopics(string|array $tokens,string $specificTopic): void
    {
        if (empty($specificTopic)) return;
        $specificTopic = $this->getTopics($specificTopic);

        if(is_string($tokens)) $tokens = [$tokens];

        foreach ($tokens as $token) {
            $subTopics = [];

            try {
                $appInstance = $this->messaging->getAppInstance($token);

                $subscriptions = $appInstance->topicSubscriptions();

                foreach ($subscriptions as $subscription) {
                    $subTopic = $subscription->topic();

                    if (str_contains($subTopic, $specificTopic)) {
                        $subTopics[] = $subTopic;
                    }
                }

                if ($subTopics) {
                    $this->messaging->unsubscribeFromTopics($subTopics, $token);
                }
            } catch (\Throwable $e) {
                Log::error($e->getMessage());
            }
        }
    }

    /**
     * 退訂所有主題
     * @param $token //設備token
     */
    public function unsubAllTopic(string $token): void
    {

        try {
            $appInstance = $this->messaging->getAppInstance($token);

            $subscriptions = $appInstance->topicSubscriptions();

            $subTopics = [];
            foreach ($subscriptions as $subscription) {
                $subTopic = $subscription->topic();

                    if (str_contains($subTopic, $this->topic_prefix)) {
                        $subTopics[] = $subTopic;
                    }
            }

            if ($subTopics) {
                $this->messaging->unsubscribeFromTopics($subTopics, $token);
            }
        } catch (\Throwable $e) {
            Log::error($e->getMessage());
        }
    }


}
