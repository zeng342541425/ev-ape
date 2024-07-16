<?php

namespace App\Http\Controllers\WebSocket;

use Symfony\Component\HttpFoundation\Response as HttpResponse;

interface WebSocket
{
    /**
     * WebSocket constructor.
     * @param string $clientId 全局唯壹的客戶端socket連接標識
     */
    public function __construct(string $clientId);

    /**
     * 發送數據
     * @param HttpResponse $response
     */
    public function send(HttpResponse $response): void;
}
