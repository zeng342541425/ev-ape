<?php

namespace App\Http\Middleware;

use App\Services\Common\SignService;
use App\Traits\ReturnJson;
use Illuminate\Http\JsonResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PileAuth
{
    use ReturnJson;


    /**
     *
     * @param Request $request
     * @param Closure $next
     * @return JsonResponse|mixed
     */
    public function handle(Request $request, Closure $next)
    {

        $params = $request->only(['serial_number', 'timestamp', 'nonce']);
        $sign = $request->post('sign');

        Log::info('post params', ['data' => $params, 'sign' => $sign]);

        if (empty($params['serial_number']) || strlen($params['serial_number']) <= 0) {
            $msg = '缺少字段 serial_number';
            return $this->returnJson($msg, [], 403);
        }

        if (empty($params['nonce']) || strlen($params['nonce']) <= 0) {
            $msg = '缺少字段 nonce';
            return $this->returnJson($msg, [], 403);
        }

        if (empty($sign) || strlen($sign) <= 0) {
            $msg = '缺少字段 sign';
            return $this->returnJson($msg, [], 403);
        }

        if ( empty($params['timestamp']) || !is_numeric($params['timestamp'])) {
            $msg = '缺少參數 timestamp';
            return $this->returnJson($msg, [], 403);
        }

        $config = config('pile');

        $expired_time = $config['expired_time'] ?? 10;

        if($params['timestamp'] < (time() - $expired_time)){
            $msg = 'API超時';
            return $this->returnJson($msg, [], 403);
        }

        $params['timestamp'] = strval($params['timestamp']);
        $params['token'] = $config['token'];
        if (!SignService::checkSignature($params, $sign)) {
            $msg = '簽名驗證失敗';
            return $this->returnJson($msg, [], 403);
        }

        return $next($request);

    }

}
