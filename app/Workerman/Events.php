<?php

namespace App\Workerman;

use App;
use App\Constants\ReturnCode;
use Exception;
use GatewayWorker\BusinessWorker;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\Utils;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
// use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Illuminate\Support\Facades\Redis;
use Laravel\Sanctum\Sanctum;
// use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
// use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
// use PHPOpenSourceSaver\JWTAuth\Providers\Auth\Illuminate;
// use PHPOpenSourceSaver\JWTAuth\Token;
use App\Models\Sanctum\PersonalAccessToken;

class Events
{
    /**
     * 當businessWorker進程啟動時觸發。每個進程生命周期內都只會觸發壹次
     *
     * @param BusinessWorker $businessWorker
     */
    public static function onWorkerStart(BusinessWorker $businessWorker): void
    {
        // GateWay::log()->info('OnWorkerStart', [
        //     'registerAddress' => $businessWorker->registerAddress,
        //     'name' => $businessWorker->name,
        //     'count' => $businessWorker->count,
        //     'workerId' => $businessWorker->workerId,
        // ]);

        Log::channel('workerman')->info('OnWorkerStart', [
            'registerAddress' => $businessWorker->registerAddress,
            'name'            => $businessWorker->name,
            'count'           => $businessWorker->count,
            'workerId'        => $businessWorker->workerId,
        ]);

    }

    /**
     * 當客戶端連接上gateway進程時(TCP三次握手完畢時)觸發的回調函數。
     * onConnect事件僅僅代表客戶端與gateway完成了TCP三次握手，這時客戶端還沒有發來任何數據，
     * 此時除了通過$_SERVER['REMOTE_ADDR']獲得對方ip，沒有其他可以鑒別客戶端的數據或者信息，所以在onConnect事件裏無法確認對方是誰。
     *
     * @param string $clientId 全局唯壹的客戶端socket連接標識
     */
    public static function onConnect(string $clientId): void
    {
        Log::channel('workerman')->info('onConnect', [
            'clientId' => $clientId
        ]);

    }

    /**
     * 當客戶端連接上gateway完成websocket握手時觸發的回調函數
     *
     * @param string $clientId 全局唯壹的客戶端socket連接標識
     * @param array $data websocket握手時的http頭數據，包含get、server等變量
     */
    public static function onWebSocketConnect(string $clientId, array $data): void
    {

        Log::channel('workerman')->info('onWebSocketConnect', [
            'clientId' => $clientId, 'data' => $data
        ]);

        if (isset($data['get']['token'])) {
            try {
                Log::channel('workerman')->info('onWebSocketConnect', [
                    'token' => $data['get']['token']
                ]);
                $accessToken = PersonalAccessToken::findToken($data['get']['token']);
                Log::channel('workerman')->info('onWebSocketConnect', [
                    'accessToken' => $accessToken->toArray()
                ]);

                if ($accessToken) {
                    $user = $accessToken->tokenable_type::query()->find($accessToken->tokenable_id);
                    GateWay::bindUser($clientId, $user);
                    $return = \response()->json([
                            'code'     => ReturnCode::OK,
                            'type'     => __FUNCTION__,
                            'msg'      => '連接成功',
                            'uid'      => $user->id,
                            'clientId' => $clientId,
                        ]
                    );
                    Log::channel('workerman')->info('onWebSocketConnect', [
                        'return' => $return,
                        'token' => $data['get']['token'],
                    ]);
                } else {
                    // token 無效
                    throw new Exception("Info is false");
                }

            }  catch (\Throwable $e) {
                $return = \response()->json([
                        'code' => ReturnCode::NEED_LOGIN,
                        'type' => __FUNCTION__,
                        'msg'  => $e->getMessage(),
                    ]
                );
                Log::channel('workerman')->error('onWebSocketConnect2', [
                    'data' => $return,
                    'msg'  => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                    'token' => $data['get']['token'],
                ]);
            }
            GateWay::sendResponseToClient($clientId, $return);
        }

    }

    /**
     * 當客戶端發來數據(Gateway進程收到數據)後觸發的回調函數
     *
     * @param string $clientId 全局唯壹的客戶端socket連接標識
     * @param mixed $message 完整的客戶端請求數據，數據類型取決於Gateway所使用協議的decode方法返的回值類型
     */
    public static function onMessage(string $clientId, $message): void
    {
        // cmd
        // $type = ['type' => __FUNCTION__];
        // try {
        //     $message = Utils::jsonDecode($message);
        //     if (is_object($message) && isset($message->route) && is_string($message->route)) {
        //         GateWay::cmd($clientId, $message->route, collect($message->data ?? null));
        //     } else {
        //         $return = ResponseBuilder::asError(ReturnCode::ERROR)
        //             ->withData($type)
        //             ->withMessage(__('message.common.error.json_error'))
        //             ->build();
        //         Gateway::sendResponseToClient($clientId, $return);
        //     }
        // } catch (InvalidArgumentException $exception) {
        //     $return = ResponseBuilder::asError(ReturnCode::ERROR)
        //         ->withData($type)
        //         ->withMessage(__('message.common.error.json_error'))
        //         ->build();
        //     Gateway::sendResponseToClient($clientId, $return);
        // }
    }

    /**
     * 客戶端與Gateway進程的連接斷開時觸發。
     * 不管是客戶端主動斷開還是服務端主動斷開，都會觸發這個回調。壹般在這裏做壹些數據清理工作
     *
     * @param string $clientId 全局唯壹的客戶端socket連接標識
     */
    public static function onClose(string $clientId): void
    {
        try {


            // Redis::sadd('workerMan:userId:'.$user_id, $clientId);
            // Redis::expire('workerMan:userId:'.$user_id, 1000 * 24 * 3600);
            //
            // Redis::setex('workerMan:clientId:' . $clientId, 1000 * 3600, $user_id);

            $user_id = Redis::get('workerMan:clientId:' . $clientId);
            if ($user_id !== null) {
                Redis::srem('workerMan:userId:'.$user_id, $clientId);
                // Redis::del('workerman-userId-' . $userId, $clientId);
            }
            Redis::del('workerMan:clientId:' . $clientId);

            Log::channel('workerman')->info('onClose', ['clientid' => $clientId]);
        } catch (\Throwable $e) {
            Log::channel('workerman')->error('onClose', [
                'msg'  => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
        }

    }
}
