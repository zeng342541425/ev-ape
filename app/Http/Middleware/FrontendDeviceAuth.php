<?php

namespace App\Http\Middleware;


use App\Models\Order\Order;
use App\Traits\ReturnJson;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FrontendDeviceAuth
{
    use ReturnJson;


    /**
     *
     * @param Request $request
     * @param Closure $next
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function handle(Request $request, Closure $next)
    {

        // 驗證header攜帶參數
        // Authorization
        // version: 版本号 如：1.1.1, 如果是瀏覽器web端，恆爲1
        // webapp： Web, iOS ,Android三種
        // device : 设备唯一标示
        // language : 多語言
        $debug = config('app.debug');
        $webapp = $request->header('webapp');
        if ( !in_array(strtolower($webapp), ['web', 'ios', 'android']) ) {
            return $debug ? $this->error('header 缺少 webapp') : $this->error();
        }

        $device_id = $request->header('device-id');
        if ( !$device_id ) {
            return $debug ? $this->error('header 缺少 device-id') : $this->error();
        }

        $version = $request->header('version');
        if ( !$version ) {
            return $debug ? $this->error('header 缺少 version') : $this->error();
        }

        $device = $request->header('device');
        if ( !$device ) {
            return $debug ? $this->error('header 缺少 device') : $this->error();
        }

        // $language = $request->header('language');
        // if ( !$language ) {
        //     return $debug ? $this->error('header 缺少 language') : $this->error();
        // }

        if ($debug === true) {
            $data_log = [
                'requestData' => $request->all(),
                'requestIp' => $request->getClientIp(),
                'route' => $request->route(),
            ];
            Log::info('request:', $data_log);
        }

        return $next($request);

    }

}
