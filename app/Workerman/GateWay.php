<?php


namespace App\Workerman;

use App\Models\Frontend\User\User;
use Exception;
use GatewayWorker\Lib\Gateway as LibGateWay;
use GuzzleHttp\Utils;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class GateWay extends LibGateWay
{
    /**
     * @return LoggerInterface
     */
    public static function log(): LoggerInterface
    {
        return Log::channel('workerman');
    }

    /**
     * @param $clientId
     * @param $info
     * @return void
     */
    public static function bindUser($clientId, $info): void
    {
        // parent::bindUid($clientId, Utils::jsonEncode([
        //     'model' => get_class($info),
        //     'id' => $info->id
        // ]));

        // Redis::set('workerman-userId-' . $info->id, $clientId);
        // Redis::setex('workerman-userId-' . $info->id, 24 * 3600, $clientId);

        // Redis::setex('workerman-clientId-' . $clientId, 24 * 3600, $info->id);

        $user_id = $info->id;
        Redis::sadd('workerMan:userId:'.$user_id, $clientId);
        Redis::expire('workerMan:userId:'.$user_id, 1000 * 24 * 3600);

        Redis::setex('workerMan:clientId:' . $clientId, 1000 * 3600, $user_id);

    }

    /**
     * @param string $clientId
     * @return User|User|Builder|Builder|\Illuminate\Database\Eloquent\Collection|Model|mixed|null
     */
    public static function getUserByClientId(string $clientId)
    {
        $uid = Redis::get('workerMan:clientId:' . $clientId);
        if ($uid === null) {
            return null;
        }

        return User::query()->find($uid);

    }

    /**
     * @param string $clientId
     * @param HttpResponse $response
     */
    public static function sendResponseToClient(string $clientId, HttpResponse $response): void
    {
        parent::sendToClient($clientId, $response->getContent());
    }

    /**
     * @param HttpResponse $response
     * @param array|null $clientId
     * @param array|null $excludeClientId
     * @throws Exception
     */
    public static function sendResponseToAll(
        HttpResponse $response,
        array $clientId = null,
        array $excludeClientId = null
    ): void {
        parent::sendToAll($response->getContent(), $clientId, $excludeClientId);
    }

    /**
     * @param string $clientId
     * @param string $routerName
     * @param Collection $collection
     */
    public static function cmd(string $clientId, string $routerName, Collection $collection): void
    {
        $route = app()->routes->getByName($routerName);
        if ($route) {
            $uses = $route->getAction('uses');
            list($controller, $method) = explode('@', $uses);
            if (class_exists($controller) && method_exists($call = new $controller($clientId), $method)) {
                $call->$method($collection);
            }
        }
    }
}
