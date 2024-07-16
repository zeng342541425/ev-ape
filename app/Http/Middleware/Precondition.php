<?php

namespace App\Http\Middleware;

use App\Constants\ReturnCode;
use App\Models\Frontend\User\User;
use App\Models\Order\Order;
use App\Traits\ReturnJson;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class Precondition
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

        $user = $request->user();
        $order_info = Order::query()->select('id')->where('user_id', $user['id'])->whereNotIn('status', [1,2])->first();
        if ($order_info) {
            $msg = '您有充電記錄未支付，功能不能使用';
            return $this->returnJson($msg, [], 403);
        }

        return $next($request);

    }

}
