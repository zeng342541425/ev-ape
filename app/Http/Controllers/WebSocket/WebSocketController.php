<?php

namespace App\Http\Controllers\WebSocket;

use App\Http\Controllers\Controller;
use App\Workerman\GateWay;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class WebSocketController extends Controller implements WebSocket
{

    public ?string $clientId;

    /**
     * WebSocket constructor.
     * @param string|null $clientId 全局唯壹的客戶端socket連接標識
     */
    public function __construct(string $clientId = null)
    {
        $this->clientId = $clientId;
    }

    /**
     * 發送數據
     * @param HttpResponse $response
     */
    public function send(HttpResponse $response): void
    {
        GateWay::sendResponseToClient($this->clientId, $response);
    }
}
