<?php

namespace App\Http\Middleware;

use App\Constants\ReturnCode;
use App\Models\Backend\Admin\Admin;
use App\Traits\ReturnJson;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuthAdmin
{
    use ReturnJson;

    public function handle(Request $request, Closure $next): SymfonyResponse
    {
        //檢測用戶憑證是否有效
        $user = auth()->user();
        if ($user) {
            return $next($request);
        }

        return $this->needLogin();
    }

    /**
     * Handle an incoming request
     * @param Request $request
     * @param Closure $next
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    // public function handle(Request $request, Closure $next)
    // {
    //     try {
    //         $jwtAuth = JWTAuth::parseToken();
    //         if ($jwtAuth->getClaim('role') != Admin::JWT_CUSTOM_CLAIM_ROLE) {
    //             return $this->needLogin();
    //         }
    //         $admin = $jwtAuth->authenticate();
    //     } catch (TokenExpiredException  $exception) {
    //         // Token 已過期
    //         try {
    //             // 刷新 Token
    //             $jwtAuth->refresh();
    //
    //             if ($jwtAuth->getClaim('role') != Admin::JWT_CUSTOM_CLAIM_ROLE) {
    //                 return $this->needLogin();
    //             }
    //
    //             Auth::onceUsingId(JWTAuth::manager()->getPayloadFactory()->buildClaimsCollection()->toPlainArray()['sub']);
    //
    //             // 生成新 Token
    //             $admin = Auth::user();
    //             $token = Auth::login($admin);
    //
    //             // 返回新 Token
    //             $response = $next($request);
    //             $response->headers->set('Authorization', 'Bearer ' . $token);
    //             $this->loginAdmin($admin);
    //             return $response;
    //
    //         } catch (\Throwable $exception) {
    //             // 刷新失敗，請重新登入
    //             return $this->needLogin();
    //         }
    //
    //     } catch (JWTException $exception) {
    //         // 沒有傳 Token
    //         return $this->needLogin();
    //     } catch (\Throwable $exception) {
    //         return $this->needLogin($exception->getMessage());
    //     }
    //
    //     return $next($request);
    // }


    /**
     * 需要登入
     * @param string $msg
     * @param mixed|null $data
     * @param $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function needLogin(string $msg = '', mixed $data = null, $code = ReturnCode::NEED_LOGIN)
    {
        $msg = $msg ?: __('api_code.unauthorized');

        return $this->returnJson($msg, compact('data'), $code);
    }


    public function loginAdmin($admin)
    {
        $admin->update([
            'last_login_time' => Carbon::now()
        ]);
    }

}
