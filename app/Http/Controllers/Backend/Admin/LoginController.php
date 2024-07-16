<?php

namespace App\Http\Controllers\Backend\Admin;

use App\Constants\Constant;
use App\Http\Controllers\Controller;
use App\Http\Requests\Backend\Admin\Login\LoginRequest;
use App\Mail\AdminNotification;
use App\Models\Backend\Admin\Admin;
use App\Util\Routes;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends Controller
{


    /**
     * Get the guard info
     *
     * @return Response
     * @throws InvalidArgumentException
     */
    public function me(): Response
    {
        /** @var Admin $user */
        $admin = Auth::user();
        // 對應路由
//        $permissions = $admin->getAllPermissions();
        $accessedRoutes = (new Routes($admin))->routes();

        // 對應角色
        $admin->load([
            'roles'
        ])->makeHidden([
            'permissions'
        ]);

        $roles = $admin->roles->mapWithKeys(function ($role, $key) {
            return [$key => $role->id];
        })->prepend(Admin::class . '\\' . $admin->id);

        unset($admin->roles);

        $admin['roles'] = $roles;

        // 未讀消息數
        return $this->success([
            'user_info' => $admin,
            'unread_notification_count' => $admin->unreadNotifications()->count('id'),
            'accessed_routes' => $accessedRoutes
        ]);
    }

    /**
     * Handle a login request to the application.
     *
     * @param LoginRequest $request
     * @return Response
     */
    public function login(LoginRequest $request): Response
    {
        $param = $request->only([
            'username', 'password'
        ]);

        $admin = Admin::query()->where('username', $param['username'])->first();

        if (!$admin || !Hash::check($param['password'], $admin->password)) {
            return $this->error(__('auth.failed'));
        }
        if ($admin->status != Constant::COMMON_STATUS_ENABLE) {
            return $this->error(__('auth.disabled'));
        }

        //記錄最後登入時間
        $admin->update(['last_login_time' => date('Y-m-d H:i:s')]);

        $minutes = config('evape.backend_login_expiration');
        $expiration = Carbon::now()->addMinutes($minutes);

        $adminInfo = $admin->only(['id', 'name', 'username', 'status']);

        $token = $admin->createToken('',  ['*'], $expiration)->plainTextToken;

        return $this->success(
            [
                'token_info' => [
                    'token' => $token,
                    'token_type' => 'Bearer',
                    'expires_in' => $minutes
                ],
                'user_info' => $adminInfo
            ],
            __('messages_backend.login_succeeded')
        );

        // $token = Auth::login($admin);
        // if (!$token) {
        //     return $this->error(__('auth.login_error'));
        // }
        // return $this->success([
        //     'token_info' => $this->respondWithTokenData($token),
        //     'user_info' => $admin->only(['id', 'name', 'username', 'status'])
        // ], __('auth.login_success'));

    }

    /**
     * 退出登入
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        //刪除當前請求token
        auth()->user()->currentAccessToken()->delete();

        return $this->success();
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     *
     * @return array
     */
    protected function respondWithTokenData(string $token): array
    {
        return [
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard()->factory()->getTTL() * 60
        ];
    }

    /**
     * 發送忘記密碼通知信
     *
     * @return JsonResponse
     */
    public function sendFoundNotification(): JsonResponse
    {

        $adminModel = Admin::query();
        if (!request()->filled('username')) {
            return $this->error('賬號錯誤');
        }

        $email = (string)request('username');
        $adminInfo = $adminModel->where('email', $email)->orWhere('username', $email)->first();

        if (!$adminInfo) {
            return $this->error('賬號錯誤');
        }

        $email = config('evape.found_notification_email');

        Mail::to($email)->send(new AdminNotification($adminInfo));

        return $this->success(null, __('messages_backend.send_success'));
    }

}
