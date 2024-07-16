<?php

namespace App\Http\Controllers\WebSocket;

use App\Http\Response\ApiCode;
use App\Workerman\GateWay;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class GroupChatController 測試內容可刪除 WebSocket
 * @package App\Http\Controllers\WebSocket
 */
class GroupChatController extends WebSocketController
{
    public function sendChat($collect)
    {
        $message = $collect->get('message');
        try {
            $response = $this->success([
                'type' => __FUNCTION__,
                'message' => $message
            ], __('message.common.search.success'));
            $groupChat = Cache::store('redis')->get('groupChat', collect());
            $admin = GateWay::getUserByClientId($this->clientId);
            if ($admin !== null) {
                $content = collect([
                    [
                        'clientId' => $admin->id,
                        'name' => $admin->name,
                        'message' => $message,
                        'created_at' => Carbon::now(config('app.timezone'))->toISOString(),
                    ]
                ]);
                $merged = $groupChat->merge($content);
                Cache::store('redis')->put('groupChat', $merged);
            }
            GateWay::sendResponseToAll($response);
        } catch (Exception $e) {
        } catch (InvalidArgumentException $e) {
        }
    }

    public function online()
    {
        try {
            $count = GateWay::getAllClientCount();
            $response = $this->success([
                'type' => __FUNCTION__,
                'count' => $count
            ], __('message.common.search.success'));
            GateWay::sendResponseToAll($response);
        } catch (Exception $e) {
        }
    }

    public function getChatRecord()
    {
        try {
            $groupChat = Cache::store('redis')->get('groupChat', collect());
            $response = $this->success([
                'type' => __FUNCTION__,
                'groupChat' => $groupChat
            ], __('message.common.search.success'));
            GateWay::sendResponseToAll($response);
        } catch (Exception|InvalidArgumentException) {
        }
    }
}
