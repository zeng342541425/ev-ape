<?php

namespace App\Util\Firebase;





class FirebaseLite extends FirebaseManager
{

    public const TOPIC_ARR = [
        1 => "member_all",  //全部

    ];


    /**
     * @param int $type
     * @param string $title
     * @param string $body
     * @param array $data
     * @return void
     */
    public function sendByType(int $type, string $title, string $body, array $data = []): void
    {

        $this->sendByTopics(self::TOPIC_ARR[$type], $title, $body, $data);
    }

    /**
     * @param array $types
     * @param string $token
     * @return void
     */
    public function subTopicsByTypes(array $types,string $token): void
    {
        $this->subTopics($token,array_intersect_key(self::TOPIC_ARR,array_flip($types)));
    }

    /**
     * @param array $types
     * @param string $token
     * @return void
     */
    public function unsubTopicsByTypes(array $types,string $token): void
    {

        foreach ($types as $v){
            $this->unsubTopics($token,self::TOPIC_ARR[$v]);
        }

    }


}
